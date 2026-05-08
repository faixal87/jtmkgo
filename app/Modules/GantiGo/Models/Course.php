<?php

namespace App\Modules\GantiGo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'master_course_id',
        'semester_id',
        'programme_id',
        'course_code',
        'course_name',
        'class_name',
        'is_active',
        'created_by',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function masterCourse(): BelongsTo
    {
        return $this->belongsTo(MasterCourse::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classReplacements(): HasMany
    {
        return $this->hasMany(ClassReplacement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOffered(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('semester_courses')
                    ->whereColumn('semester_courses.master_course_id', 'courses.master_course_id')
                    ->whereColumn('semester_courses.semester_id', 'courses.semester_id')
                    ->where('semester_courses.is_offered', true);
            });
    }

    public function setCourseCodeAttribute(?string $value): void
    {
        $this->attributes['course_code'] = $value === null ? null : strtoupper(trim($value));
    }

    public function getCourseCodeAttribute(?string $value): ?string
    {
        return $value === null ? null : strtoupper($value);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
