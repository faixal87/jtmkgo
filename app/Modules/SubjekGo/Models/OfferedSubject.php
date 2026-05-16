<?php

namespace App\Modules\SubjekGo\Models;

use App\Models\User;
use App\Modules\GantiGo\Models\ClassGroup;
use App\Modules\GantiGo\Models\Programme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfferedSubject extends Model
{
    protected $table = 'subjek_go_offered_subjects';

    protected $fillable = [
        'session_id',
        'programme_id',
        'course_code',
        'course_name',
        'curriculum_version',
        'offered_semester',
        'credit_hour',
        'weekly_contact_hour',
        'total_class_groups',
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
        )->withPivot(['academic_advisor_user_id'])->withTimestamps();
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
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! filled($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('course_code', 'like', "%{$search}%")
                ->orWhere('course_name', 'like', "%{$search}%")
                ->orWhere('offered_semester', 'like', "%{$search}%")
                ->orWhereHas('programme', fn (Builder $query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
        });
    }

    public function getLabelAttribute(): string
    {
        return "{$this->course_code} - {$this->course_name}";
    }

    public function setCourseCodeAttribute(?string $value): void
    {
        $this->attributes['course_code'] = $value === null ? null : strtoupper(trim($value));
    }

    protected function casts(): array
    {
        return [
            'credit_hour' => 'decimal:2',
            'weekly_contact_hour' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
