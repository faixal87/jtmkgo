<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\GantiGo\Models\ClassReplacement;
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
    'mbot_membership',
    'bem_membership',
    'account_status',
    'approved_at',
    'approved_by',
    'is_super_admin',
    'theme_preference',
    'theme',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
                ->orWhere('email', 'like', "%{$search}%");
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

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo ? Storage::url($this->profile_photo) : null;
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
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
