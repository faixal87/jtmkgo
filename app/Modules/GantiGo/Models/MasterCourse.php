<?php

namespace App\Modules\GantiGo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterCourse extends Model
{
    protected $fillable = [
        'course_code',
        'course_name',
        'programme_id',
        'is_active',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function semesterCourses(): HasMany
    {
        return $this->hasMany(SemesterCourse::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function setCourseCodeAttribute(?string $value): void
    {
        $this->attributes['course_code'] = $value === null ? null : strtoupper(trim($value));
    }

    public function getCourseCodeAttribute(?string $value): ?string
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
