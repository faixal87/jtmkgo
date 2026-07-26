<?php

namespace App\Modules\RubricGrading\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradingSession extends Model
{
    use SoftDeletes;

    protected $table = 'rubric_grading_sessions';

    protected $fillable = [
        'rubric_id',
        'user_id',
        'name',
        'class_group',
        'assessment_date',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(SessionStudent::class, 'grading_session_id')->orderBy('sort_order')->orderBy('name');
    }

    public function scopeVisibleTo(Builder $query, User $user, bool $canManage = false): Builder
    {
        if ($user->is_super_admin || $canManage) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('class_group', 'like', "%{$search}%")
                ->orWhereHas('rubric', fn (Builder $query) => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('course_code', 'like', "%{$search}%"))
                ->orWhereHas('lecturer', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }

    public function canBeManagedBy(User $user, bool $canManage = false): bool
    {
        return ! $user->is_super_admin && ($this->user_id === $user->id || $canManage);
    }

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
        ];
    }
}
