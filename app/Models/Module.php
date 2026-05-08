<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'route_prefix',
        'description',
        'is_active',
    ];

    public function userAccesses(): HasMany
    {
        return $this->hasMany(ModuleUserAccess::class);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'module_admins')
            ->withPivot(['id', 'assigned_by', 'assigned_at', 'is_active'])
            ->withTimestamps();
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(ModuleAccessRequest::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
