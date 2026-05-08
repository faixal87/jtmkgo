<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleUserAccess extends Model
{
    protected $table = 'module_user_access';

    protected $fillable = [
        'user_id',
        'module_id',
        'granted_by',
        'granted_at',
        'is_active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
