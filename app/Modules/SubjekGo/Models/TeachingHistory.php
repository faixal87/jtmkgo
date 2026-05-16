<?php

namespace App\Modules\SubjekGo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingHistory extends Model
{
    protected $table = 'subjek_go_teaching_histories';

    protected $fillable = [
        'user_id',
        'offered_subject_id',
        'course_code',
        'course_name',
        'academic_session',
        'semester_name',
        'class_group',
        'weekly_contact_hour',
        'taught_duration_months',
        'remarks',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function offeredSubject(): BelongsTo
    {
        return $this->belongsTo(OfferedSubject::class);
    }

    public function scopeForLecturer(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function setCourseCodeAttribute(?string $value): void
    {
        $this->attributes['course_code'] = $value === null ? null : strtoupper(trim($value));
    }

    protected function casts(): array
    {
        return [
            'weekly_contact_hour' => 'decimal:2',
        ];
    }
}
