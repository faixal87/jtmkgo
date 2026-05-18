<?php

namespace App\Modules\AcademicCore\Models;

use App\Models\User;
use App\Modules\GantiGo\Models\Programme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicSubjectOffering extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'academic_semester_id',
        'academic_subject_id',
        'programme_id',
        'curriculum_version',
        'offered_semester',
        'coordinator_user_id',
        'remarks',
        'is_active',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(AcademicSemester::class, 'academic_semester_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'academic_subject_id');
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }

    public function classGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicClassGroup::class,
            'academic_subject_offering_class_groups',
            'academic_subject_offering_id',
            'academic_class_group_id'
        )->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereNull('archived_at');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! filled($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->whereHas('subject', fn (Builder $query) => $query
                    ->where('course_code', 'like', "%{$search}%")
                    ->orWhere('course_name', 'like', "%{$search}%"))
                ->orWhere('curriculum_version', 'like', "%{$search}%")
                ->orWhere('offered_semester', 'like', "%{$search}%")
                ->orWhereHas('programme', fn (Builder $query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
        });
    }

    public function getLabelAttribute(): string
    {
        return trim(($this->subject?->course_code ?? 'Unknown subject').' - '.($this->subject?->course_name ?? ''));
    }

    public function getTotalClassGroupsAttribute(): int
    {
        if (array_key_exists('class_groups_count', $this->attributes)) {
            return (int) $this->attributes['class_groups_count'];
        }

        if ($this->relationLoaded('classGroups')) {
            return $this->classGroups->count();
        }

        return $this->classGroups()->count();
    }

    public function scopeOrderBySubjectCode(Builder $query): Builder
    {
        return $query->orderBy(
            AcademicSubject::query()
                ->select('course_code')
                ->whereColumn('academic_subjects.id', 'academic_subject_offerings.academic_subject_id')
        );
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
