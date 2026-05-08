<?php

namespace App\Modules\GantiGo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterClassGroup extends Model
{
    protected $fillable = [
        'class_group_name',
        'programme_id',
        'is_active',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function semesterClassGroups(): HasMany
    {
        return $this->hasMany(SemesterClassGroup::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function setClassGroupNameAttribute(?string $value): void
    {
        $this->attributes['class_group_name'] = $value === null ? null : strtoupper(trim($value));
    }

    public function getClassGroupNameAttribute(?string $value): ?string
    {
        return $value === null ? null : strtoupper($value);
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
