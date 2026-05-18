<?php

namespace App\Modules\SubjekGo\Models;

use App\Models\User;
use App\Modules\AcademicCore\Models\AcademicSemester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model
{
    use SoftDeletes;

    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_PUBLIC = 'public';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'subjek_go_sessions';

    protected $fillable = [
        'name',
        'academic_session',
        'academic_semester_id',
        'description',
        'visibility',
        'status',
        'open_at',
        'close_at',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function academicSemester(): BelongsTo
    {
        return $this->belongsTo(AcademicSemester::class);
    }

    public function offeredSubjects(): HasMany
    {
        return $this->hasMany(OfferedSubject::class);
    }

    public function activeOfferedSubjects(): HasMany
    {
        return $this->offeredSubjects()->where('is_active', true);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(Preference::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function isOpenForSelection(): bool
    {
        if ($this->status !== self::STATUS_OPEN) {
            return false;
        }

        if ($this->open_at && $this->open_at->isFuture()) {
            return false;
        }

        return ! $this->close_at || $this->close_at->greaterThanOrEqualTo(now());
    }

    protected function casts(): array
    {
        return [
            'open_at' => 'datetime',
            'close_at' => 'datetime',
        ];
    }
}
