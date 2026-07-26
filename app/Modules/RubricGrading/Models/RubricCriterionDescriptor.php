<?php

namespace App\Modules\RubricGrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubricCriterionDescriptor extends Model
{
    protected $table = 'rubric_grading_criterion_descriptors';

    protected $fillable = [
        'criterion_id',
        'level_id',
        'description',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'criterion_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(RubricLevel::class, 'level_id');
    }
}
