<?php

namespace App\Modules\SubjekGo\Models;

use App\Modules\GantiGo\Models\Programme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassGroup extends Model
{
    protected $table = 'subjek_go_class_groups';

    protected $fillable = [
        'programme_id',
        'class_name',
        'cohort',
        'current_semester',
        'remarks',
        'is_active',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function offeredSubjects(): BelongsToMany
    {
        return $this->belongsToMany(
            OfferedSubject::class,
            'subjek_go_subject_class_groups',
            'class_group_id',
            'offered_subject_id'
        )->withTimestamps();
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
                ->where('class_name', 'like', "%{$search}%")
                ->orWhere('cohort', 'like', "%{$search}%")
                ->orWhere('current_semester', 'like', "%{$search}%")
                ->orWhereHas('programme', fn (Builder $query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
        });
    }

    public function getClassNameAttribute(?string $value): ?string
    {
        return $value === null ? null : strtoupper($value);
    }

    public function setClassNameAttribute(?string $value): void
    {
        $this->attributes['class_name'] = $value === null ? null : strtoupper(trim($value));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
