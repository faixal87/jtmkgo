<?php

namespace App\Modules\RubricGrading\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rubric extends Model
{
    use SoftDeletes;

    protected $table = 'rubric_grading_rubrics';

    protected $fillable = [
        'user_id',
        'title',
        'course_code',
        'course_name',
        'assessment_type',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(RubricLevel::class)->orderBy('sort_order');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('sort_order');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GradingSession::class);
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
                ->where('title', 'like', "%{$search}%")
                ->orWhere('course_code', 'like', "%{$search}%")
                ->orWhere('course_name', 'like', "%{$search}%")
                ->orWhere('assessment_type', 'like', "%{$search}%")
                ->orWhereHas('lecturer', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }

    public function canBeManagedBy(User $user, bool $canManage = false): bool
    {
        return ! $user->is_super_admin && ($this->user_id === $user->id || $canManage);
    }

    public function totalWeight(): float
    {
        $criteria = $this->relationLoaded('criteria') ? $this->criteria : $this->criteria()->get();

        return round((float) $criteria->sum(fn (RubricCriterion $criterion) => (float) $criterion->weight), 2);
    }
}
