<?php

namespace App\Modules\GantiGo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassGroup extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'master_class_group_id',
        'programme_id',
        'semester_id',
        'class_name',
        'is_active',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function masterClassGroup(): BelongsTo
    {
        return $this->belongsTo(MasterClassGroup::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function replacements(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassReplacement::class,
            'class_replacement_classes',
            'class_id',
            'class_replacement_id'
        )->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOffered(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('semester_class_groups')
                    ->whereColumn('semester_class_groups.master_class_group_id', 'classes.master_class_group_id')
                    ->whereColumn('semester_class_groups.semester_id', 'classes.semester_id')
                    ->where('semester_class_groups.is_offered', true);
            });
    }

    public function setClassNameAttribute(?string $value): void
    {
        $this->attributes['class_name'] = $value === null ? null : strtoupper(trim($value));
    }

    public function getClassNameAttribute(?string $value): ?string
    {
        return $value === null ? null : strtoupper($value);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
