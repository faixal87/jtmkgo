<?php

namespace App\Modules\RubricGrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentScore extends Model
{
    protected $table = 'rubric_grading_student_scores';

    protected $fillable = [
        'session_student_id',
        'criterion_id',
        'level_value',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(SessionStudent::class, 'session_student_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'criterion_id');
    }

    protected function casts(): array
    {
        return [
            'level_value' => 'decimal:2',
        ];
    }
}
