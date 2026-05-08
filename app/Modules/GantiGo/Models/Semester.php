<?php

namespace App\Modules\GantiGo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'name',
        'session_code',
        'start_date',
        'end_date',
        'is_active',
        'auto_activate',
        'remarks',
        'created_by',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function activeCourses(): HasMany
    {
        return $this->courses()->where('is_active', true);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function classReplacements(): HasMany
    {
        return $this->hasMany(ClassReplacement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereDate('end_date', '<', now()->toDateString());
    }

    public function isArchived(): bool
    {
        return $this->end_date->isPast() && ! $this->end_date->isToday();
    }

    public function isCurrentByDate(): bool
    {
        $today = now()->toDateString();

        return $this->start_date->toDateString() <= $today
            && $this->end_date->toDateString() >= $today;
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'auto_activate' => 'boolean',
        ];
    }
}
