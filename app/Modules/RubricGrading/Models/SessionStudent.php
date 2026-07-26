<?php

namespace App\Modules\RubricGrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionStudent extends Model
{
    use SoftDeletes;

    protected $table = 'rubric_grading_session_students';

    protected $fillable = [
        'grading_session_id',
        'name',
        'registration_no',
        'remarks',
        'sort_order',
    ];

    public function gradingSession(): BelongsTo
    {
        return $this->belongsTo(GradingSession::class, 'grading_session_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentScore::class, 'session_student_id');
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
