<?php

namespace App\Providers;

use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Policies\ClassReplacementPolicy;
use App\Modules\GantiGo\Policies\GantiGoPolicy;
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
        Gate::define('view-ganti-go', [GantiGoPolicy::class, 'view']);
        Gate::define('manage-ganti-go', [GantiGoPolicy::class, 'manage']);
    }
}
