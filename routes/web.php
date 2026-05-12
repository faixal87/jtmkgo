<?php

use App\Http\Controllers\ModuleAdmin\ModuleAccessController;
use App\Http\Controllers\ModuleAccessRequestController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\NotificationComposerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\AccessControlController;
use App\Http\Controllers\SuperAdmin\BrandingSettingsController;
use App\Http\Controllers\SuperAdmin\ModuleController;
use App\Http\Controllers\SuperAdmin\UserApprovalController;
use App\Http\Controllers\SuperAdmin\UserImportController;
use App\Models\Module;
use App\Support\SafeArrayCache;
use App\Modules\GantiGo\Controllers\AdminReplacementController as GantiGoAdminReplacementController;
use App\Modules\GantiGo\Controllers\ClassReplacementController as GantiGoClassReplacementController;
use App\Modules\GantiGo\Controllers\ClassGroupController as GantiGoClassGroupController;
use App\Modules\GantiGo\Controllers\CourseController as GantiGoCourseController;
use App\Modules\GantiGo\Controllers\DashboardController as GantiGoDashboardController;
use App\Modules\GantiGo\Controllers\GantiGoSettingController;
use App\Modules\GantiGo\Controllers\ImportController as GantiGoImportController;
use App\Modules\GantiGo\Controllers\ProgrammeController as GantiGoProgrammeController;
use App\Modules\GantiGo\Controllers\SemesterController as GantiGoSemesterController;
use App\Modules\PhotoRepository\Controllers\Admin\CategoryController as PhotoRepositoryCategoryController;
use App\Modules\PhotoRepository\Controllers\Admin\AnalyticsController as PhotoRepositoryAnalyticsController;
use App\Modules\PhotoRepository\Controllers\Admin\PhotoManagementController as PhotoRepositoryPhotoManagementController;
use App\Modules\PhotoRepository\Controllers\Admin\ProfileController as PhotoRepositoryProfileController;
use App\Modules\PhotoRepository\Controllers\Admin\ReviewQueueController as PhotoRepositoryReviewQueueController;
use App\Modules\PhotoRepository\Controllers\DashboardController as PhotoRepositoryDashboardController;
use App\Modules\PhotoRepository\Controllers\GalleryController as PhotoRepositoryGalleryController;
use App\Modules\PhotoRepository\Controllers\MyPhotosController as PhotoRepositoryMyPhotosController;
use App\Modules\PhotoRepository\Controllers\PhotoController as PhotoRepositoryPhotoController;
use App\Modules\PhotoRepository\Controllers\PhotoDownloadController as PhotoRepositoryPhotoDownloadController;
use App\Modules\PhotoRepository\Controllers\UploadPhotoController as PhotoRepositoryUploadPhotoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pending-approval', function (Request $request) {
    $user = $request->user();

    if ($user->account_status === 'approved') {
        return redirect()->route('dashboard');
    }

    if ($user->account_status !== 'pending') {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $user->account_status === 'inactive'
            ? 'Your account is inactive. Please contact the administrator.'
            : 'Your account has been rejected. Please contact the administrator.';

        return redirect()->route('login')->with('status', $message);
    }

    return view('auth.pending-approval');
})->middleware(['auth', 'session.timeout'])->name('pending-approval');

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();

    $dashboardModuleData = SafeArrayCache::remember("dashboard.modules.{$user->id}", now()->addSeconds(30), function () use ($user) {
        if ($user->is_super_admin) {
            return [
                'available_modules' =>
                Module::query()
                    ->select(['id', 'name', 'slug', 'icon', 'route_prefix', 'description', 'is_active'])
                    ->where('is_active', true)
                    ->where('slug', '!=', 'passport-photo')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Module $module) => $module->only(['id', 'name', 'slug', 'icon', 'route_prefix', 'description', 'is_active']))
                    ->values()
                    ->toArray(),
                'managed_module_ids' => [],
            ];
        }

        if ($user->account_status !== 'approved') {
            return ['available_modules' => [], 'managed_module_ids' => []];
        }

        return [
            'available_modules' =>
            $user->accessibleModules()
                ->select(['modules.id', 'modules.name', 'modules.slug', 'modules.icon', 'modules.route_prefix', 'modules.description', 'modules.is_active'])
                ->where('modules.is_active', true)
                ->where('modules.slug', '!=', 'passport-photo')
                ->wherePivot('is_active', true)
                ->orderBy('modules.name')
                ->get()
                ->map(fn (Module $module) => $module->only(['id', 'name', 'slug', 'icon', 'route_prefix', 'description', 'is_active']))
                ->values()
                ->toArray(),
            'managed_module_ids' =>
            $user->adminModules()
                ->wherePivot('is_active', true)
                ->pluck('modules.id')
                ->values()
                ->toArray(),
        ];
    }, ['available_modules', 'managed_module_ids']);
    $availableModules = Module::hydrate($dashboardModuleData['available_modules'] ?? []);
    $managedModuleIds = collect($dashboardModuleData['managed_module_ids'] ?? []);

    return view('dashboard', compact('availableModules', 'managedModuleIds'));
})->middleware(['auth', 'session.timeout', 'verified', 'approved'])->name('dashboard');

Route::middleware(['auth', 'session.timeout', 'verified', 'approved', 'super.admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/users', [UserApprovalController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserApprovalController::class, 'create'])->name('users.create');
        Route::post('/users', [UserApprovalController::class, 'store'])->name('users.store');
        Route::get('/users/pending', [UserApprovalController::class, 'pending'])->name('users.pending');
        Route::patch('/users/bulk-approve', [UserApprovalController::class, 'bulkApprove'])->name('users.bulk-approve');
        Route::get('/users/import', [UserImportController::class, 'create'])->name('users.import.create');
        Route::post('/users/import', [UserImportController::class, 'store'])->name('users.import.store');
        Route::get('/users/{user}/edit', [UserApprovalController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserApprovalController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/approve', [UserApprovalController::class, 'approve'])->name('users.approve');
        Route::patch('/users/{user}/reject', [UserApprovalController::class, 'reject'])->name('users.reject');
        Route::patch('/users/{user}/deactivate', [UserApprovalController::class, 'deactivate'])->name('users.deactivate');
        Route::patch('/users/{user}/reset-password', [UserApprovalController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::patch('/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');

        Route::get('/settings/branding', [BrandingSettingsController::class, 'edit'])->name('settings.branding.edit');
        Route::patch('/settings/branding', [BrandingSettingsController::class, 'update'])->name('settings.branding.update');
        Route::post('/settings/branding/reset', [BrandingSettingsController::class, 'reset'])->name('settings.branding.reset');

        Route::get('/access-control', [AccessControlController::class, 'index'])->name('access-control.index');
        Route::get('/access-control/users/search', [AccessControlController::class, 'searchUsers'])->name('access-control.users.search');
        Route::post('/access-control/module-access/toggle', [AccessControlController::class, 'toggleModuleAccess'])->name('access-control.module-access.toggle');
        Route::post('/access-control/module-access/bulk', [AccessControlController::class, 'bulkModuleAccess'])->name('access-control.module-access.bulk');
        Route::post('/access-control/module-admin/toggle', [AccessControlController::class, 'toggleModuleAdmin'])->name('access-control.module-admin.toggle');
        Route::post('/access-control/access', [AccessControlController::class, 'grantAccess'])->name('access-control.grant');
        Route::delete('/access-control/access/{access}', [AccessControlController::class, 'revokeAccess'])->name('access-control.revoke');
        Route::delete('/access-control/users/{user}/access', [AccessControlController::class, 'revokeUserAccess'])->name('access-control.revoke-user-access');
        Route::post('/access-control/module-admins', [AccessControlController::class, 'assignModuleAdmin'])->name('access-control.assign-admin');
        Route::delete('/access-control/module-admins/{admin}', [AccessControlController::class, 'revokeModuleAdmin'])->name('access-control.revoke-admin');
        Route::delete('/access-control/users/{user}/module-admins', [AccessControlController::class, 'revokeUserModuleAdmins'])->name('access-control.revoke-user-admins');
    });

Route::middleware(['auth', 'session.timeout', 'verified', 'approved', 'module.admin'])
    ->prefix('module-admin')
    ->name('module-admin.')
    ->group(function () {
        Route::get('/{module:slug}/access', [ModuleAccessController::class, 'index'])->name('access.index');
        Route::post('/{module:slug}/access', [ModuleAccessController::class, 'grant'])->name('access.grant');
        Route::delete('/{module:slug}/access/{access}', [ModuleAccessController::class, 'revoke'])->name('access.revoke');
    });

Route::middleware(['auth', 'session.timeout', 'verified', 'approved', 'module.access:ganti-go'])
    ->prefix('ganti-go')
    ->name('ganti-go.')
    ->group(function () {
        Route::get('/', GantiGoDashboardController::class)->name('dashboard');
        Route::get('analytics', [GantiGoAdminReplacementController::class, 'analytics'])->name('analytics');
        Route::get('replacements', [GantiGoClassReplacementController::class, 'index'])->name('replacements.index');
        Route::get('replacements/create', [GantiGoClassReplacementController::class, 'create'])->name('replacements.create');
        Route::post('replacements', [GantiGoClassReplacementController::class, 'store'])->name('replacements.store');
        Route::get('replacements/{classReplacement}', [GantiGoClassReplacementController::class, 'show'])->name('replacements.show');
        Route::get('replacements/{classReplacement}/edit', [GantiGoClassReplacementController::class, 'edit'])->name('replacements.edit');
        Route::patch('replacements/{classReplacement}', [GantiGoClassReplacementController::class, 'update'])->name('replacements.update');
        Route::patch('replacements/{classReplacement}/cancel', [GantiGoClassReplacementController::class, 'cancel'])->name('replacements.cancel');
        Route::patch('replacements/{classReplacement}/submit-implementation', [GantiGoClassReplacementController::class, 'submitImplementation'])->name('replacements.submit-implementation');
        Route::get('replacements/{classReplacement}/evidence', [GantiGoClassReplacementController::class, 'downloadEvidence'])->name('replacements.evidence');

        Route::middleware('module.admin:ganti-go')->group(function () {
            Route::get('admin/monitoring', [GantiGoAdminReplacementController::class, 'monitoring'])->name('admin.monitoring');
            Route::get('admin/review-queue', [GantiGoAdminReplacementController::class, 'reviewQueue'])->name('admin.review-queue');
            Route::patch('admin/replacements/{classReplacement}/approve', [GantiGoAdminReplacementController::class, 'approve'])->name('admin.replacements.approve');
            Route::patch('admin/replacements/{classReplacement}/reject', [GantiGoAdminReplacementController::class, 'reject'])->name('admin.replacements.reject');

            Route::resource('semesters', GantiGoSemesterController::class)->except(['show', 'destroy']);
            Route::patch('semesters/{semester}/activate', [GantiGoSemesterController::class, 'activate'])->name('semesters.activate');
            Route::get('semesters/{semester}/setup', [GantiGoSemesterController::class, 'setup'])->name('semesters.setup');
            Route::patch('semesters/{semester}/offerings', [GantiGoSemesterController::class, 'syncOfferings'])->name('semesters.offerings.sync');

            Route::patch('courses/{course}/toggle', [GantiGoCourseController::class, 'toggle'])->name('courses.toggle');
            Route::resource('courses', GantiGoCourseController::class)->except(['show', 'destroy']);

            Route::patch('programmes/{programme}/toggle', [GantiGoProgrammeController::class, 'toggle'])->name('programmes.toggle');
            Route::resource('programmes', GantiGoProgrammeController::class)->except(['show', 'destroy']);

            Route::patch('classes/{classGroup}/toggle', [GantiGoClassGroupController::class, 'toggle'])->name('classes.toggle');
            Route::resource('classes', GantiGoClassGroupController::class)->parameters(['classes' => 'classGroup'])->except(['show', 'destroy']);

            Route::get('settings', [GantiGoSettingController::class, 'edit'])->name('settings.edit');
            Route::patch('settings', [GantiGoSettingController::class, 'update'])->name('settings.update');

            Route::get('import', [GantiGoImportController::class, 'index'])->name('import.index');
            Route::post('import/preview', [GantiGoImportController::class, 'preview'])->name('import.preview');
        });
    });

Route::middleware(['auth', 'session.timeout', 'verified', 'approved', 'module.access:photo-repository'])
    ->prefix('photo-repository')
    ->name('photo-repository.')
    ->group(function () {
        Route::get('/', PhotoRepositoryDashboardController::class)->name('dashboard');
        Route::get('/gallery', [PhotoRepositoryGalleryController::class, 'index'])->name('gallery');
        Route::get('/my-photos', [PhotoRepositoryMyPhotosController::class, 'index'])->name('my-photos');
        Route::get('/upload', [PhotoRepositoryUploadPhotoController::class, 'create'])->name('upload.create');
        Route::post('/upload', [PhotoRepositoryUploadPhotoController::class, 'store'])->name('upload.store');
        Route::get('/photos/{mediaPhoto}', [PhotoRepositoryPhotoController::class, 'show'])->name('photos.show');
        Route::get('/photos/{mediaPhoto}/download', PhotoRepositoryPhotoDownloadController::class)->name('photos.download');

        Route::middleware('module.admin:photo-repository')->group(function () {
            Route::get('/admin/analytics', PhotoRepositoryAnalyticsController::class)->name('admin.analytics');
            Route::get('/admin/review-queue', [PhotoRepositoryReviewQueueController::class, 'index'])->name('admin.review-queue');
            Route::patch('/admin/photos/{mediaPhoto}/approve', [PhotoRepositoryReviewQueueController::class, 'approve'])->name('admin.photos.approve');
            Route::patch('/admin/photos/{mediaPhoto}/reject', [PhotoRepositoryReviewQueueController::class, 'reject'])->name('admin.photos.reject');
            Route::patch('/admin/photos/{mediaPhoto}/archive', [PhotoRepositoryPhotoManagementController::class, 'archive'])->name('admin.photos.archive');
            Route::delete('/admin/photos/{mediaPhoto}', [PhotoRepositoryPhotoManagementController::class, 'destroy'])->name('admin.photos.destroy');

            Route::get('/admin/profiles', [PhotoRepositoryProfileController::class, 'index'])->name('admin.profiles');
            Route::post('/admin/profiles', [PhotoRepositoryProfileController::class, 'store'])->name('admin.profiles.store');
            Route::patch('/admin/profiles/{mediaProfile}/toggle', [PhotoRepositoryProfileController::class, 'toggle'])->name('admin.profiles.toggle');

            Route::get('/admin/categories', [PhotoRepositoryCategoryController::class, 'index'])->name('admin.categories');
            Route::post('/admin/categories', [PhotoRepositoryCategoryController::class, 'store'])->name('admin.categories.store');
            Route::patch('/admin/categories/{mediaCategory}', [PhotoRepositoryCategoryController::class, 'update'])->name('admin.categories.update');
            Route::patch('/admin/categories/{mediaCategory}/toggle', [PhotoRepositoryCategoryController::class, 'toggle'])->name('admin.categories.toggle');
        });
    });

Route::middleware(['auth', 'session.timeout', 'approved'])->group(function () {
    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationCenterController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationCenterController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/{notification}/unread', [NotificationCenterController::class, 'markUnread'])->name('notifications.unread');

    Route::get('/admin/notifications', [NotificationComposerController::class, 'create'])->name('admin.notifications.create');
    Route::post('/admin/notifications', [NotificationComposerController::class, 'store'])->name('admin.notifications.store');

    Route::get('/module-access-requests', [ModuleAccessRequestController::class, 'index'])->name('module-access-requests.index');
    Route::post('/module-access-requests', [ModuleAccessRequestController::class, 'store'])->name('module-access-requests.store');
    Route::get('/admin/module-access-requests', [ModuleAccessRequestController::class, 'adminIndex'])->name('admin.module-access-requests.index');
    Route::patch('/admin/module-access-requests/{moduleAccessRequest}/approve', [ModuleAccessRequestController::class, 'approve'])->name('admin.module-access-requests.approve');
    Route::patch('/admin/module-access-requests/{moduleAccessRequest}/reject', [ModuleAccessRequestController::class, 'reject'])->name('admin.module-access-requests.reject');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
