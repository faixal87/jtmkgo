<?php

namespace App\Modules\SubjekGo\Models;

use App\Models\User;
use App\Modules\AcademicCore\Models\AcademicSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingExperience extends Model
{
    public const LEVEL_BEGINNER = 'beginner';
    public const LEVEL_FAMILIAR = 'familiar';
    public const LEVEL_EXPERIENCED = 'experienced';
    public const LEVEL_EXPERT = 'expert';

    protected $table = 'subjek_go_teaching_experiences';

    protected $fillable = [
        'user_id',
        'academic_subject_id',
        'experience_years',
        'experience_level',
        'last_taught_session',
        'remarks',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'academic_subject_id');
    }

    public function scopeForLecturer(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! filled($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('last_taught_session', 'like', "%{$search}%")
                ->orWhere('remarks', 'like', "%{$search}%")
                ->orWhereHas('subject', fn (Builder $subjectQuery) => $subjectQuery
                    ->where('course_code', 'like', "%{$search}%")
                    ->orWhere('course_name', 'like', "%{$search}%"));
        });
    }

    protected function casts(): array
    {
        return [
            'experience_years' => 'decimal:2',
        ];
    }
}
