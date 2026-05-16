<?php

namespace App\Modules\SubjekGo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Preference extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_LOCKED = 'locked';

    protected $table = 'subjek_go_preferences';

    protected $fillable = [
        'session_id',
        'user_id',
        'choice_1_subject_id',
        'choice_2_subject_id',
        'choice_3_subject_id',
        'choice_4_subject_id',
        'total_selected_contact_hour',
        'submitted_at',
        'status',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function choiceOne(): BelongsTo
    {
        return $this->belongsTo(OfferedSubject::class, 'choice_1_subject_id');
    }

    public function choiceTwo(): BelongsTo
    {
        return $this->belongsTo(OfferedSubject::class, 'choice_2_subject_id');
    }

    public function choiceThree(): BelongsTo
    {
        return $this->belongsTo(OfferedSubject::class, 'choice_3_subject_id');
    }

    public function choiceFour(): BelongsTo
    {
        return $this->belongsTo(OfferedSubject::class, 'choice_4_subject_id');
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_LOCKED]);
    }

    /**
     * @return Collection<int, OfferedSubject>
     */
    public function selectedSubjects(): Collection
    {
        return collect([
            $this->choiceOne,
            $this->choiceTwo,
            $this->choiceThree,
            $this->choiceFour,
        ])->filter()->values();
    }

    public function choiceIds(): array
    {
        return [
            1 => $this->choice_1_subject_id,
            2 => $this->choice_2_subject_id,
            3 => $this->choice_3_subject_id,
            4 => $this->choice_4_subject_id,
        ];
    }

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'total_selected_contact_hour' => 'decimal:2',
        ];
    }
}
