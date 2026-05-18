<?php

namespace App\Modules\SubjekGo\Models;

use App\Models\User;
use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\GantiGo\Models\Programme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferedSubject extends Model
{
    use SoftDeletes;

    protected $table = 'subjek_go_offered_subjects';

    protected $fillable = [
        'session_id',
        'programme_id',
        'subject_master_id',
        'academic_subject_offering_id',
        'curriculum_version',
        'offered_semester',
        'subject_coordinator_user_id',
        'remarks',
        'is_active',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function subjectMaster(): BelongsTo
    {
        return $this->belongsTo(SubjectMaster::class);
    }

    public function academicSubjectOffering(): BelongsTo
    {
        return $this->belongsTo(AcademicSubjectOffering::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_coordinator_user_id');
    }

    public function subjectClassGroups(): HasMany
    {
        return $this->hasMany(SubjectClassGroup::class);
    }

    public function classGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassGroup::class,
            'subjek_go_subject_class_groups',
            'offered_subject_id',
            'class_group_id'
        )->withTimestamps();
    }

    public function teachingHistories(): HasMany
    {
        return $this->hasMany(TeachingHistory::class);
    }

    public function choiceOnePreferences(): HasMany
    {
        return $this->hasMany(Preference::class, 'choice_1_subject_id');
    }

    public function choiceTwoPreferences(): HasMany
    {
        return $this->hasMany(Preference::class, 'choice_2_subject_id');
    }

    public function choiceThreePreferences(): HasMany
    {
        return $this->hasMany(Preference::class, 'choice_3_subject_id');
    }

    public function choiceFourPreferences(): HasMany
    {
        return $this->hasMany(Preference::class, 'choice_4_subject_id');
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
                ->whereHas('subjectMaster', fn (Builder $query) => $query
                    ->where('course_code', 'like', "%{$search}%")
                    ->orWhere('course_name', 'like', "%{$search}%"))
                ->orWhereHas('academicSubjectOffering.subject', fn (Builder $query) => $query
                    ->where('course_code', 'like', "%{$search}%")
                    ->orWhere('course_name', 'like', "%{$search}%"))
                ->orWhere('offered_semester', 'like', "%{$search}%")
                ->orWhereHas('programme', fn (Builder $query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
        });
    }

    public function getLabelAttribute(): string
    {
        return trim(($this->resolvedSubject()?->course_code ?? 'Unknown subject').' - '.($this->resolvedSubject()?->course_name ?? ''));
    }

    public function getCourseCodeAttribute(): ?string
    {
        return $this->resolvedSubject()?->course_code;
    }

    public function getCourseNameAttribute(): ?string
    {
        return $this->resolvedSubject()?->course_name;
    }

    public function getCreditHourAttribute(): ?string
    {
        return $this->resolvedSubject()?->credit_hour;
    }

    public function getWeeklyContactHourAttribute(): ?string
    {
        return $this->resolvedSubject()?->weekly_contact_hour;
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
            SubjectMaster::query()
                ->select('course_code')
                ->whereColumn('subjek_go_subject_masters.id', 'subjek_go_offered_subjects.subject_master_id')
        );
    }

    private function resolvedSubject(): mixed
    {
        return $this->academicSubjectOffering?->subject ?? $this->subjectMaster;
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
