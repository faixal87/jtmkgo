<?php

namespace App\Providers;

use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Policies\ClassReplacementPolicy;
use App\Modules\GantiGo\Policies\GantiGoPolicy;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Modules\PhotoRepository\Policies\MediaPhotoPolicy;
use App\Modules\PhotoRepository\Policies\PhotoRepositoryPolicy;
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

        Gate::define('view-ganti-go', [GantiGoPolicy::class, 'view']);
        Gate::define('manage-ganti-go', [GantiGoPolicy::class, 'manage']);
        Gate::define('view-photo-repository', [PhotoRepositoryPolicy::class, 'view']);
        Gate::define('upload-photo-repository', [PhotoRepositoryPolicy::class, 'upload']);
        Gate::define('manage-photo-repository', [PhotoRepositoryPolicy::class, 'manage']);
    }
}
