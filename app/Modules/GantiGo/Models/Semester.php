<?php

namespace App\Modules\GantiGo\Models;

use App\Models\User;
use App\Modules\AcademicCore\Models\AcademicSemester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'name',
        'session_code',
        'start_date',
        'end_date',
        'is_active',
        'auto_activate',
        'remarks',
        'created_by',
        'academic_semester_id',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function semesterCourses(): HasMany
    {
        return $this->hasMany(SemesterCourse::class);
    }

    public function offeredSemesterCourses(): HasMany
    {
        return $this->semesterCourses()->where('is_offered', true);
    }

    public function activeCourses(): HasMany
    {
        return $this->courses()->where('is_active', true);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function semesterClassGroups(): HasMany
    {
        return $this->hasMany(SemesterClassGroup::class);
    }

    public function offeredSemesterClassGroups(): HasMany
    {
        return $this->semesterClassGroups()->where('is_offered', true);
    }

    public function classReplacements(): HasMany
    {
        return $this->hasMany(ClassReplacement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function academicSemester(): BelongsTo
    {
        return $this->belongsTo(AcademicSemester::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereDate('end_date', '<', now()->toDateString());
    }

    public function isArchived(): bool
    {
        return $this->end_date->isPast() && ! $this->end_date->isToday();
    }

    public function isCurrentByDate(): bool
    {
        $today = now()->toDateString();

        return $this->start_date->toDateString() <= $today
            && $this->end_date->toDateString() >= $today;
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'auto_activate' => 'boolean',
        ];
    }
}
