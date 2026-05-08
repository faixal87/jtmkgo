<?php

namespace App\Modules\GantiGo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterCourse extends Model
{
    protected $fillable = [
        'semester_id',
        'master_course_id',
        'is_offered',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function masterCourse(): BelongsTo
    {
        return $this->belongsTo(MasterCourse::class);
    }

    public function scopeOffered(Builder $query): Builder
    {
        return $query->where('is_offered', true);
    }

    protected function casts(): array
    {
        return [
            'is_offered' => 'boolean',
        ];
    }
}
