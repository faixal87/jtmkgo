<?php

namespace App\Modules\SubjekGo\Services;

use App\Models\User;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreferenceSelectionService
{
    public function __construct(private readonly SessionWindowService $sessions)
    {
    }

    /**
     * @param  array<int, int>  $choiceIds
     */
    public function submit(User $user, Session $session, array $choiceIds): Preference
    {
        $openSession = $this->sessions->openForSelection();

        if (! $openSession || ! $session->is($openSession)) {
            throw ValidationException::withMessages([
                'session_id' => 'Selections can only be submitted for the current open session.',
            ]);
        }

        if (! $session->isOpenForSelection()) {
            throw ValidationException::withMessages([
                'session_id' => 'Subject preference session is currently closed.',
            ]);
        }

        $existing = Preference::query()
            ->where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing?->status === Preference::STATUS_LOCKED) {
            throw new AuthorizationException('This submission has been locked by the module admin.');
        }

        $subjects = OfferedSubject::query()
            ->where('session_id', $session->id)
            ->active()
            ->whereIn('id', $choiceIds)
            ->get()
            ->keyBy('id');

        if ($subjects->count() !== 4) {
            throw ValidationException::withMessages([
                'choice_1_subject_id' => 'All selected subjects must be active offerings from the current session.',
            ]);
        }

        return DB::transaction(function () use ($existing, $user, $session, $choiceIds, $subjects): Preference {
            $preference = $existing ?: new Preference([
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);

            $preference->fill([
                'choice_1_subject_id' => $choiceIds[0],
                'choice_2_subject_id' => $choiceIds[1],
                'choice_3_subject_id' => $choiceIds[2],
                'choice_4_subject_id' => $choiceIds[3],
                'total_selected_contact_hour' => $this->totalContactHours($subjects),
                'submitted_at' => now(),
                'status' => Preference::STATUS_SUBMITTED,
            ])->save();

            return $preference->fresh([
                'choiceOne',
                'choiceTwo',
                'choiceThree',
                'choiceFour',
                'session',
            ]);
        });
    }

    /**
     * @param  Collection<int, OfferedSubject>  $subjects
     */
    private function totalContactHours(Collection $subjects): float
    {
        return (float) $subjects->sum(fn (OfferedSubject $subject): float => (float) ($subject->weekly_contact_hour ?? 0));
    }
}
