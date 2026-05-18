<?php

namespace App\Modules\AcademicCore\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicSemester extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'academic_session',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'auto_activate',
        'remarks',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subjectOfferings(): HasMany
    {
        return $this->hasMany(AcademicSubjectOffering::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED
            || ($this->end_date && $this->end_date->isPast() && ! $this->end_date->isToday());
    }

    public function isCurrentByDate(): bool
    {
        if (! $this->start_date || ! $this->end_date) {
            return false;
        }

        $today = now()->toDateString();

        return $this->start_date->toDateString() <= $today
            && $this->end_date->toDateString() >= $today;
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'auto_activate' => 'boolean',
        ];
    }
}
