<?php

namespace App\Modules\SurveyGo\Services;

use App\Models\User;
use App\Models\Notification;
use App\Modules\SurveyGo\Models\Answer;
use App\Modules\SurveyGo\Models\Question;
use App\Modules\SurveyGo\Models\Response;
use App\Modules\SurveyGo\Models\Survey;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SurveyGoService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function activeForcedBaseline(): ?Survey
    {
        return Survey::query()
            ->baseline()
            ->active()
            ->where('is_forced', true)
            ->latest('launched_at')
            ->latest('id')
            ->first();
    }

    public function activeSurveyByType(string $type): ?Survey
    {
        return Survey::query()
            ->where('type', $type)
            ->active()
            ->latest('launched_at')
            ->latest('id')
            ->first();
    }

    public function latestSurveyByType(string $type): ?Survey
    {
        if (! $this->tablesReady()) {
            return null;
        }

        return Survey::query()
            ->where('type', $type)
            ->latest('launched_at')
            ->latest('id')
            ->first();
    }

    public function hasSubmitted(User $user, Survey $survey): bool
    {
        return Response::query()
            ->where('survey_id', $survey->id)
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->exists();
    }

    public function hasCompletedBaseline(User $user): bool
    {
        $baseline = $this->latestSurveyByType(Survey::TYPE_BASELINE);

        return $baseline ? $this->hasSubmitted($user, $baseline) : false;
    }

    public function canAnswer(User $user, Survey $survey): bool
    {
        if (! $survey->isOpenForResponses()) {
            return false;
        }

        if ($survey->type !== Survey::TYPE_IMPACT) {
            return true;
        }

        return $this->hasCompletedBaseline($user);
    }

    public function dueSurveyCount(User $user): int
    {
        if ($user->is_super_admin || ! $this->tablesReady()) {
            return 0;
        }

        return $this->userSurveyStatuses($user)
            ->where('is_due', true)
            ->count();
    }

    public function nextDueSurvey(User $user): ?Survey
    {
        if ($user->is_super_admin || ! $this->tablesReady()) {
            return null;
        }

        $baseline = $this->activeSurveyByType(Survey::TYPE_BASELINE);

        if ($baseline && ! $this->hasSubmitted($user, $baseline)) {
            return $baseline;
        }

        $impact = $this->activeSurveyByType(Survey::TYPE_IMPACT);

        if ($impact && $this->hasCompletedBaseline($user) && ! $this->hasSubmitted($user, $impact)) {
            return $impact;
        }

        return null;
    }

    /**
     * @return Collection<int, array{survey: Survey, submitted: bool, is_due: bool, is_locked: bool, status_text: string, action_url: string|null}>
     */
    public function userSurveyStatuses(User $user): Collection
    {
        if (! $this->tablesReady()) {
            return collect();
        }

        $baseline = $this->activeSurveyByType(Survey::TYPE_BASELINE)
            ?? $this->latestSurveyByType(Survey::TYPE_BASELINE);
        $impact = $this->activeSurveyByType(Survey::TYPE_IMPACT)
            ?? $this->latestSurveyByType(Survey::TYPE_IMPACT);

        return collect([$baseline, $impact])
            ->filter()
            ->unique('id')
            ->values()
            ->map(function (Survey $survey) use ($user): array {
                $submitted = $this->hasSubmitted($user, $survey);
                $locked = $survey->type === Survey::TYPE_IMPACT && ! $this->hasCompletedBaseline($user);
                $isDue = $survey->isOpenForResponses() && ! $submitted && ! $locked;

                return [
                    'survey' => $survey,
                    'submitted' => $submitted,
                    'is_due' => $isDue,
                    'is_locked' => $locked,
                    'status_text' => $submitted
                        ? 'Completed'
                        : ($locked ? 'Locked until Kajian Awal is completed' : ($survey->isOpenForResponses() ? 'Needs response' : $survey->statusLabel())),
                    'action_url' => $isDue ? route('survey-go.surveys.show', $survey) : null,
                ];
            });
    }

    /**
     * @param array<int|string, mixed> $answers
     */
    public function submit(Survey $survey, User $user, array $answers): Response
    {
        return DB::transaction(function () use ($survey, $user, $answers): Response {
            $response = Response::query()->firstOrCreate([
                'survey_id' => $survey->id,
                'user_id' => $user->id,
            ]);

            $questions = $survey->activeQuestions()->get(['id', 'question_type']);

            foreach ($questions as $question) {
                $value = $answers[$question->id] ?? null;

                Answer::query()->updateOrCreate(
                    [
                        'response_id' => $response->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'answer_value' => $question->question_type === Question::TYPE_SHORT_TEXT ? null : (string) $value,
                        'answer_text' => $question->question_type === Question::TYPE_SHORT_TEXT ? (string) $value : null,
                    ]
                );
            }

            $response->forceFill(['submitted_at' => now()])->save();

            return $response->refresh()->load(['survey', 'answers.question']);
        });
    }

    public function launchImpactSurvey(Survey $survey, User $actor): int
    {
        $survey->forceFill([
            'type' => Survey::TYPE_IMPACT,
            'status' => Survey::STATUS_ACTIVE,
            'is_forced' => false,
            'is_notification_sent' => true,
            'launched_at' => $survey->launched_at ?? now(),
            'closed_at' => null,
        ])->save();

        return $this->notifySurveyRecipients($survey, $actor);
    }

    public function notifySurveyRecipients(Survey $survey, User $actor): int
    {
        if (! $survey->isOpenForResponses()) {
            return 0;
        }

        $recipients = $this->respondentQuery()
            ->get(['id', 'name', 'email']);

        $sent = 0;

        foreach ($recipients as $recipient) {
            $sent += $this->notifyUserForSurvey($recipient, $survey, $actor) ? 1 : 0;
        }

        return $sent;
    }

    public function notifyUserForSurvey(User $recipient, Survey $survey, ?User $actor = null): bool
    {
        if (! $this->canAnswer($recipient, $survey) || $this->hasSubmitted($recipient, $survey)) {
            return false;
        }

        $type = "survey-go:survey:{$survey->id}";
        $alreadySent = Notification::query()
            ->where('user_id', $recipient->id)
            ->where('type', $type)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $title = $survey->type === Survey::TYPE_IMPACT
            ? 'Kajian Impak JTMK Go'
            : 'Kajian Awal JTMK Go';

        $message = $survey->type === Survey::TYPE_IMPACT
            ? "Salam {$recipient->name}, mohon kongsikan maklum balas anda selepas menggunakan JTMK Go."
            : "Salam {$recipient->name}, mohon lengkapkan Kajian Awal JTMK Go.";

        $this->notifications->send(
            $recipient,
            $title,
            $message,
            $type,
            $actor,
            route('survey-go.surveys.show', $survey),
            'Open Survey'
        );

        return true;
    }

    public function dashboardStats(): array
    {
        $totalUsers = $this->respondentQuery()->count();
        $activeBaseline = $this->activeSurveyByType(Survey::TYPE_BASELINE);
        $activeImpact = $this->activeSurveyByType(Survey::TYPE_IMPACT);
        $baseline = $activeBaseline ?? Survey::query()->baseline()->latest('id')->first();
        $impact = $activeImpact ?? Survey::query()->impact()->latest('id')->first();

        $baselineSubmitted = $baseline
            ? $baseline->submittedResponses()->count()
            : 0;
        $impactSubmitted = $impact
            ? $impact->submittedResponses()->count()
            : 0;

        $before = $baseline ? $this->averageLikertScore($baseline) : 0.0;
        $after = $impact ? $this->averageLikertScore($impact) : 0.0;

        return [
            'totalSurveys' => Survey::query()->count(),
            'activeBaseline' => $activeBaseline,
            'activeImpact' => $activeImpact,
            'baselineCompletionRate' => $totalUsers > 0 ? round(($baselineSubmitted / $totalUsers) * 100, 1) : 0,
            'impactResponseRate' => $totalUsers > 0 ? round(($impactSubmitted / $totalUsers) * 100, 1) : 0,
            'averageBefore' => round($before, 2),
            'averageAfter' => round($after, 2),
            'improvementPercentage' => $before > 0 ? round((($after - $before) / $before) * 100, 1) : 0,
        ];
    }

    public function averageLikertScore(Survey $survey): float
    {
        return (float) Answer::query()
            ->join('survey_go_questions', 'survey_go_questions.id', '=', 'survey_go_answers.question_id')
            ->join('survey_go_responses', 'survey_go_responses.id', '=', 'survey_go_answers.response_id')
            ->where('survey_go_responses.survey_id', $survey->id)
            ->whereNotNull('survey_go_responses.submitted_at')
            ->where('survey_go_questions.question_type', Question::TYPE_LIKERT)
            ->where('survey_go_questions.is_active', true)
            ->avg(DB::raw('CAST(survey_go_answers.answer_value AS DECIMAL(8,2))'));
    }

    /**
     * @return Collection<int, array{domain: string, average: float, count: int}>
     */
    public function likertAverageByDomain(?Survey $survey): Collection
    {
        if (! $survey) {
            return collect();
        }

        return Answer::query()
            ->selectRaw('COALESCE(survey_go_questions.domain, "General") as domain')
            ->selectRaw('AVG(CAST(survey_go_answers.answer_value AS DECIMAL(8,2))) as average')
            ->selectRaw('COUNT(*) as count')
            ->join('survey_go_questions', 'survey_go_questions.id', '=', 'survey_go_answers.question_id')
            ->join('survey_go_responses', 'survey_go_responses.id', '=', 'survey_go_answers.response_id')
            ->where('survey_go_responses.survey_id', $survey->id)
            ->whereNotNull('survey_go_responses.submitted_at')
            ->where('survey_go_questions.question_type', Question::TYPE_LIKERT)
            ->groupBy('domain')
            ->orderBy('domain')
            ->get()
            ->map(fn ($row) => [
                'domain' => (string) $row->domain,
                'average' => round((float) $row->average, 2),
                'count' => (int) $row->count,
            ]);
    }

    /**
     * @return Collection<int, array{question: string, yes: int, no: int, total: int, yes_percentage: float}>
     */
    public function yesNoPercentages(?Survey $survey): Collection
    {
        if (! $survey) {
            return collect();
        }

        $rows = Answer::query()
            ->selectRaw('survey_go_questions.question_text as question')
            ->selectRaw('SUM(CASE WHEN survey_go_answers.answer_value = "yes" THEN 1 ELSE 0 END) as yes_count')
            ->selectRaw('SUM(CASE WHEN survey_go_answers.answer_value = "no" THEN 1 ELSE 0 END) as no_count')
            ->join('survey_go_questions', 'survey_go_questions.id', '=', 'survey_go_answers.question_id')
            ->join('survey_go_responses', 'survey_go_responses.id', '=', 'survey_go_answers.response_id')
            ->where('survey_go_responses.survey_id', $survey->id)
            ->whereNotNull('survey_go_responses.submitted_at')
            ->where('survey_go_questions.question_type', Question::TYPE_YES_NO)
            ->groupBy('survey_go_questions.id', 'survey_go_questions.question_text')
            ->orderBy('survey_go_questions.sort_order')
            ->get();

        return $rows->map(function ($row): array {
            $yes = (int) $row->yes_count;
            $no = (int) $row->no_count;
            $total = $yes + $no;

            return [
                'question' => Question::defaultMalayQuestions()[(string) $row->question] ?? (string) $row->question,
                'yes' => $yes,
                'no' => $no,
                'total' => $total,
                'yes_percentage' => $total > 0 ? round(($yes / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * @return Collection<int, array{domain: string, before: float, after: float, combined: float, improvement: float}>
     */
    public function beforeAfterCombinedByDomain(?Survey $baseline, ?Survey $impact): Collection
    {
        $before = $this->likertAverageByDomain($baseline)->keyBy('domain');
        $after = $this->likertAverageByDomain($impact)->keyBy('domain');

        return $before->keys()
            ->merge($after->keys())
            ->unique()
            ->sort()
            ->values()
            ->map(function (string $domain) use ($before, $after): array {
                $beforeAverage = (float) ($before->get($domain)['average'] ?? 0);
                $afterAverage = (float) ($after->get($domain)['average'] ?? 0);
                $values = collect([$beforeAverage, $afterAverage])->filter(fn (float $value) => $value > 0);

                return [
                    'domain' => $domain,
                    'before' => $beforeAverage,
                    'after' => $afterAverage,
                    'combined' => $values->isNotEmpty() ? round($values->avg(), 2) : 0,
                    'improvement' => $beforeAverage > 0 ? round($afterAverage - $beforeAverage, 2) : 0,
                ];
            });
    }

    public function surveySummary(float $before, float $after): array
    {
        if ($before <= 0 || $after <= 0) {
            return [
                'label' => 'Data belum mencukupi',
                'tone' => 'amber',
                'message' => 'Jawapan Kajian Awal dan Kajian Impak diperlukan sebelum impak sistem boleh dirumuskan.',
            ];
        }

        $difference = round($after - $before, 2);

        if ($difference >= 0.5) {
            return [
                'label' => 'Impak sangat positif',
                'tone' => 'emerald',
                'message' => 'Jawapan Kajian Impak menunjukkan peningkatan yang jelas selepas penggunaan JTMK Go.',
            ];
        }

        if ($difference >= 0.15) {
            return [
                'label' => 'Impak positif',
                'tone' => 'blue',
                'message' => 'The system is showing useful improvement, with room for further refinement.',
            ];
        }

        if ($difference >= -0.14) {
            return [
                'label' => 'Impak stabil',
                'tone' => 'purple',
                'message' => 'The before and after scores are close. More responses or more usage time may be needed.',
            ];
        }

        return [
            'label' => 'Perlu penambahbaikan',
            'tone' => 'red',
            'message' => 'Skor Kajian Impak lebih rendah daripada Kajian Awal. Semak maklum balas dan tambah baik pengalaman pengguna.',
        ];
    }

    public function respondentCount(): int
    {
        return $this->respondentQuery()->count();
    }

    private function respondentQuery(): Builder
    {
        return User::query()
            ->where('account_status', 'approved')
            ->where('is_super_admin', false);
    }

    public function tablesReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('survey_go_surveys')
            && Schema::hasTable('survey_go_responses')
            && Schema::hasTable('survey_go_questions')
            && Schema::hasTable('survey_go_answers');
    }
}
