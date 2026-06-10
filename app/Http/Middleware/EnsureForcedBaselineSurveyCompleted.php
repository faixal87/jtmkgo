<?php

namespace App\Http\Middleware;

use App\Modules\SurveyGo\Services\SurveyGoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureForcedBaselineSurveyCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_super_admin || $user->account_status !== 'approved') {
            return $next($request);
        }

        if ($this->shouldSkip($request) || ! $this->tablesReady()) {
            return $next($request);
        }

        $surveyService = app(SurveyGoService::class);
        $survey = $surveyService->activeForcedBaseline();

        if (! $survey || $surveyService->hasSubmitted($user, $survey)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Salam {$user->name}, mohon lengkapkan kajian baseline ini sebelum menggunakan JTMK Go.",
                'redirect' => route('survey-go.surveys.show', ['survey' => $survey, 'forced' => 1]),
            ], 409);
        }

        return redirect()
            ->route('survey-go.surveys.show', ['survey' => $survey, 'forced' => 1])
            ->with('status', "Salam {$user->name}, mohon lengkapkan kajian baseline ini sebelum menggunakan JTMK Go.");
    }

    private function shouldSkip(Request $request): bool
    {
        return $request->routeIs(
            'survey-go.surveys.*',
            'logout',
            'login',
            'register',
            'password.*',
            'verification.*',
            'locale.update',
            'pending-approval'
        );
    }

    private function tablesReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('survey_go_surveys')
            && Schema::hasTable('survey_go_responses');
    }
}
