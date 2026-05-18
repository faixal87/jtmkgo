<?php

namespace App\Modules\SubjekGo\Models;

use App\Modules\AcademicCore\Models\AcademicSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectMaster extends Model
{
    use SoftDeletes;

    protected $table = 'subjek_go_subject_masters';

    protected $fillable = [
        'course_code',
        'course_name',
        'credit_hour',
        'weekly_contact_hour',
        'remarks',
        'is_active',
        'academic_subject_id',
    ];

    public function offerings(): HasMany
    {
        return $this->hasMany(OfferedSubject::class);
    }

    public function academicSubject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereNull('archived_at');
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

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    protected function casts(): array
    {
        return [
            'credit_hour' => 'decimal:2',
            'weekly_contact_hour' => 'decimal:2',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
