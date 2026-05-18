<?php

namespace App\Modules\SubjekGo\Models;

use App\Modules\AcademicCore\Models\AcademicClassGroup;
use App\Modules\GantiGo\Models\Programme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassGroup extends Model
{
    use SoftDeletes;

    protected $table = 'subjek_go_class_groups';

    protected $fillable = [
        'programme_id',
        'class_name',
        'cohort',
        'current_semester',
        'remarks',
        'is_active',
        'academic_class_group_id',
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

    public function academicClassGroup(): BelongsTo
    {
        return $this->belongsTo(AcademicClassGroup::class);
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
