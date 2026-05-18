<?php

namespace App\Modules\AcademicCore\Models;

use App\Modules\GantiGo\Models\Programme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicClassGroup extends Model
{
    use SoftDeletes;

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

    public function offerings(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicSubjectOffering::class,
            'academic_subject_offering_class_groups',
            'academic_class_group_id',
            'academic_subject_offering_id'
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
