<?php

namespace App\Modules\SubjekGo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectMaster extends Model
{
    protected $table = 'subjek_go_subject_masters';

    protected $fillable = [
        'course_code',
        'course_name',
        'credit_hour',
        'weekly_contact_hour',
        'remarks',
        'is_active',
    ];

    public function offerings(): HasMany
    {
        return $this->hasMany(OfferedSubject::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! filled($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('course_code', 'like', "%{$search}%")
                ->orWhere('course_name', 'like', "%{$search}%");
        });
    }

    public function getLabelAttribute(): string
    {
        return "{$this->course_code} - {$this->course_name}";
    }

    public function getCourseCodeAttribute(?string $value): ?string
    {
        return $value === null ? null : strtoupper($value);
    }

    public function setCourseCodeAttribute(?string $value): void
    {
        $this->attributes['course_code'] = $value === null ? null : strtoupper(trim($value));
    }

    protected function casts(): array
    {
        return [
            'credit_hour' => 'decimal:2',
            'weekly_contact_hour' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
