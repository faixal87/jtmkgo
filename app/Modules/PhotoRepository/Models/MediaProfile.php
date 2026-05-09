<?php

namespace App\Modules\PhotoRepository\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaProfile extends Model
{
    public const TYPE_INTERNAL = 'internal';
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_VIP = 'vip';
    public const TYPE_MANAGEMENT = 'management';

    protected $fillable = [
        'linked_user_id',
        'name',
        'designation',
        'department',
        'organization',
        'email',
        'phone',
        'profile_type',
        'has_login_account',
        'is_active',
        'created_by',
    ];

    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MediaPhoto::class);
    }

    public function approvedPhotos(): HasMany
    {
        return $this->photos()->where('status', MediaPhoto::STATUS_APPROVED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('designation', 'like', "%{$search}%")
                ->orWhere('department', 'like', "%{$search}%")
                ->orWhere('organization', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    protected function casts(): array
    {
        return [
            'has_login_account' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
