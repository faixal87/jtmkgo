<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\PasswordResetMail;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\LinkGo\Models\Link as LinkGoLink;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Modules\ProgramGo\Models\ProgramActivity;
use App\Modules\RubricGrading\Models\GradingSession as RubricGradingSession;
use App\Modules\RubricGrading\Models\Rubric as RubricGradingRubric;
use App\Modules\SubjekGo\Models\Preference as SubjekGoPreference;
use App\Modules\SubjekGo\Models\TeachingExperience as SubjekGoTeachingExperience;
use App\Modules\SubjekGo\Models\TeachingHistory as SubjekGoTeachingHistory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'email',
    'password',
    'force_password_change',
    'ic_number',
    'phone',
    'profile_photo',
    'date_of_birth',
    'department',
    'position',
    'grade',
    'staff_short_code',
    'mbot_membership',
    'bem_membership',
    'audit_requirement_link',
    'account_status',
    'approved_at',
    'approved_by',
    'is_super_admin',
    'theme_preference',
    'language_preference',
    'theme',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        Mail::to($this->getEmailForPasswordReset())->send(new PasswordResetMail($this, $resetUrl));
    }

    public function scopeApprovedStaff(Builder $query): Builder
    {
        return $query
            ->where('account_status', 'approved')
            ->where('is_super_admin', false);
    }

    public function scopeSearchIdentity(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('ic_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('staff_short_code', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'approved_by');
    }

    public function moduleAccesses(): HasMany
    {
        return $this->hasMany(ModuleUserAccess::class);
    }

    public function accessibleModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_user_access')
            ->withPivot(['id', 'granted_by', 'granted_at', 'is_active'])
            ->withTimestamps();
    }

    public function adminModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_admins')
            ->withPivot(['id', 'assigned_by', 'assigned_at', 'is_active'])
            ->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function featurePermissions(): HasMany
    {
        return $this->hasMany(FeaturePermission::class);
    }

    public function activeFeaturePermissions(): HasMany
    {
        return $this->featurePermissions()->where('is_active', true);
    }

    public function createdNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'created_by');
    }

    public function moduleAccessRequests(): HasMany
    {
        return $this->hasMany(ModuleAccessRequest::class);
    }

    public function reviewedModuleAccessRequests(): HasMany
    {
        return $this->hasMany(ModuleAccessRequest::class, 'reviewed_by');
    }

    public function classReplacements(): HasMany
    {
        return $this->hasMany(ClassReplacement::class);
    }

    public function programGoActivities(): HasMany
    {
        return $this->hasMany(ProgramActivity::class);
    }

    public function linkGoLinks(): HasMany
    {
        return $this->hasMany(LinkGoLink::class);
    }

    public function rubricGradingRubrics(): HasMany
    {
        return $this->hasMany(RubricGradingRubric::class);
    }

    public function rubricGradingSessions(): HasMany
    {
        return $this->hasMany(RubricGradingSession::class);
    }

    public function subjekGoPreferences(): HasMany
    {
        return $this->hasMany(SubjekGoPreference::class);
    }

    public function subjekGoTeachingHistories(): HasMany
    {
        return $this->hasMany(SubjekGoTeachingHistory::class);
    }

    public function subjekGoTeachingExperiences(): HasMany
    {
        return $this->hasMany(SubjekGoTeachingExperience::class);
    }

    public function profilePhotoUrl(): ?string
    {
        if ($this->profile_photo) {
            return Storage::url($this->profile_photo);
        }

        return $this->officialRepositoryPhotoUrl();
    }

    public function officialRepositoryPhotoUrl(): ?string
    {
        static $photoRepositoryTablesExist = null;
        static $repositoryPhotoUrlCache = [];
        $photoRepositoryTablesExist ??= Schema::hasTable('media_profiles') && Schema::hasTable('media_photos');

        if (! $photoRepositoryTablesExist || ! $this->getKey()) {
            return null;
        }

        if (array_key_exists($this->getKey(), $repositoryPhotoUrlCache)) {
            return $repositoryPhotoUrlCache[$this->getKey()];
        }

        $photo = MediaPhoto::query()
            ->approved()
            ->whereHas('profile', fn (Builder $query) => $query->where('linked_user_id', $this->id))
            ->orderByDesc('is_current_official')
            ->latest('approved_at')
            ->latest()
            ->first();

        return $repositoryPhotoUrlCache[$this->getKey()] = $photo?->thumbnailUrl() ?: $photo?->photoUrl();
    }

    /**
     * Absolute local file path of the user's photo, for inline email
     * embedding (remote URLs don't resolve for external mail clients).
     */
    public function profilePhotoStoragePath(): ?string
    {
        if ($this->profile_photo && Storage::disk('public')->exists($this->profile_photo)) {
            return Storage::disk('public')->path($this->profile_photo);
        }

        static $photoRepositoryTablesExist = null;
        $photoRepositoryTablesExist ??= Schema::hasTable('media_profiles') && Schema::hasTable('media_photos');

        if (! $photoRepositoryTablesExist || ! $this->getKey()) {
            return null;
        }

        $photo = MediaPhoto::query()
            ->approved()
            ->whereHas('profile', fn (Builder $query) => $query->where('linked_user_id', $this->id))
            ->orderByDesc('is_current_official')
            ->latest('approved_at')
            ->latest()
            ->first();

        foreach ([$photo?->thumbnail_path, $photo?->photo_path] as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->path($path);
            }
        }

        return null;
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }

    public function setStaffShortCodeAttribute(?string $value): void
    {
        $this->attributes['staff_short_code'] = filled($value)
            ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $value))
            : null;
    }

    public function maskedIcNumber(): string
    {
        $icNumber = (string) $this->ic_number;

        if (strlen($icNumber) <= 4) {
            return str_repeat('*', strlen($icNumber));
        }

        return substr($icNumber, 0, 2)
            .str_repeat('*', max(strlen($icNumber) - 6, 0))
            .substr($icNumber, -4);
    }

    public function hasFeaturePermission(string $permissionKey): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        static $featurePermissionsTableExists = null;
        $featurePermissionsTableExists ??= Schema::hasTable('feature_permissions');

        if (! $featurePermissionsTableExists) {
            return false;
        }

        if ($this->relationLoaded('featurePermissions')) {
            return $this->featurePermissions
                ->where('permission_key', $permissionKey)
                ->where('is_active', true)
                ->isNotEmpty();
        }

        return $this->featurePermissions()
            ->where('permission_key', $permissionKey)
            ->where('is_active', true)
            ->exists();
    }

    public function canViewSensitiveStaffDirectory(): bool
    {
        return $this->hasFeaturePermission(FeaturePermission::STAFF_DIRECTORY_SENSITIVE_VIEW);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'force_password_change' => 'boolean',
            'approved_at' => 'datetime',
            'date_of_birth' => 'date',
            'is_super_admin' => 'boolean',
        ];
    }
}
