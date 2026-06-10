<?php

namespace App\Modules\SurveyGo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    protected $table = 'survey_go_answers';

    protected $fillable = [
        'response_id',
        'question_id',
        'answer_value',
        'answer_text',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class, 'response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
