<?php

namespace App\Providers;

use App\Modules\AcademicCore\Policies\AcademicCorePolicy;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Policies\ClassReplacementPolicy;
use App\Modules\GantiGo\Policies\GantiGoPolicy;
use App\Modules\LinkGo\Policies\LinkGoPolicy;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Modules\PhotoRepository\Policies\MediaPhotoPolicy;
use App\Modules\PhotoRepository\Policies\PhotoRepositoryPolicy;
use App\Modules\ProgramGo\Policies\ProgramGoPolicy;
use App\Modules\RubricGrading\Policies\RubricGradingPolicy;
use App\Modules\SubjekGo\Models\Preference as SubjekGoPreference;
use App\Modules\SubjekGo\Policies\PreferencePolicy as SubjekGoPreferencePolicy;
use App\Modules\SubjekGo\Policies\SubjekGoPolicy;
use App\Modules\SurveyGo\Policies\SurveyGoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ClassReplacement::class, ClassReplacementPolicy::class);
        Gate::policy(MediaPhoto::class, MediaPhotoPolicy::class);
        Gate::policy(SubjekGoPreference::class, SubjekGoPreferencePolicy::class);

        Gate::define('view-ganti-go', [GantiGoPolicy::class, 'view']);
        Gate::define('manage-ganti-go', [GantiGoPolicy::class, 'manage']);
        Gate::define('view-academic-core', [AcademicCorePolicy::class, 'view']);
        Gate::define('manage-academic-core', [AcademicCorePolicy::class, 'manage']);
        Gate::define('view-photo-repository', [PhotoRepositoryPolicy::class, 'view']);
        Gate::define('upload-photo-repository', [PhotoRepositoryPolicy::class, 'upload']);
        Gate::define('manage-photo-repository', [PhotoRepositoryPolicy::class, 'manage']);
        Gate::define('view-program-go', [ProgramGoPolicy::class, 'view']);
        Gate::define('submit-program-go', [ProgramGoPolicy::class, 'submit']);
        Gate::define('manage-program-go', [ProgramGoPolicy::class, 'manage']);
        Gate::define('view-program-go-analytics', [ProgramGoPolicy::class, 'viewAnalytics']);
        Gate::define('view-rubric-grading', [RubricGradingPolicy::class, 'view']);
        Gate::define('grade-rubric-grading', [RubricGradingPolicy::class, 'grade']);
        Gate::define('manage-rubric-grading', [RubricGradingPolicy::class, 'manage']);
        Gate::define('view-rubric-grading-analytics', [RubricGradingPolicy::class, 'viewAnalytics']);
        Gate::define('view-link-go', [LinkGoPolicy::class, 'view']);
        Gate::define('submit-link-go', [LinkGoPolicy::class, 'submit']);
        Gate::define('manage-link-go', [LinkGoPolicy::class, 'manage']);
        Gate::define('view-link-go-analytics', [LinkGoPolicy::class, 'viewAnalytics']);
        Gate::define('answer-survey-go', [SurveyGoPolicy::class, 'answer']);
        Gate::define('manage-survey-go', [SurveyGoPolicy::class, 'manage']);
        Gate::define('view-survey-go-analytics', [SurveyGoPolicy::class, 'viewAnalytics']);
        Gate::define('view-subjek-go', [SubjekGoPolicy::class, 'view']);
        Gate::define('select-subjek-go', [SubjekGoPolicy::class, 'select']);
        Gate::define('manage-subjek-go', [SubjekGoPolicy::class, 'manage']);
        Gate::define('view-subjek-go-analytics', [SubjekGoPolicy::class, 'viewAnalytics']);
    }
}
