<?php

namespace App\Modules\GantiGo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programme extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function classReplacements(): HasMany
    {
        return $this->hasMany(ClassReplacement::class);
    }

    public function getCodeAttribute(?string $value): ?string
    {
        return $value === null ? null : strtoupper($value);
    }

    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = $value === null ? null : strtoupper(trim($value));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
