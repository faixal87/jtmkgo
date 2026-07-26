<?php

namespace App\Modules\RubricGrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricCriterion extends Model
{
    protected $table = 'rubric_grading_criteria';

    protected $fillable = [
        'rubric_id',
        'name',
        'weight',
        'sort_order',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function descriptors(): HasMany
    {
        return $this->hasMany(RubricCriterionDescriptor::class, 'criterion_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentScore::class, 'criterion_id');
    }

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }
}
