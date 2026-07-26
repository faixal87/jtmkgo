<?php

namespace App\Modules\RubricGrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricLevel extends Model
{
    protected $table = 'rubric_grading_levels';

    protected $fillable = [
        'rubric_id',
        'label',
        'value',
        'sort_order',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function descriptors(): HasMany
    {
        return $this->hasMany(RubricCriterionDescriptor::class, 'level_id');
    }

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }
}
