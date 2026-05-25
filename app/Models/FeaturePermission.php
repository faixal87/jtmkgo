<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturePermission extends Model
{
    public const STAFF_DIRECTORY_SENSITIVE_VIEW = 'staff-directory-sensitive-view';

    protected $fillable = [
        'user_id',
        'permission_key',
        'is_active',
        'granted_by',
        'granted_at',
        'revoked_at',
    ];

    public static function allowedKeys(): array
    {
        return [
            self::STAFF_DIRECTORY_SENSITIVE_VIEW,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
