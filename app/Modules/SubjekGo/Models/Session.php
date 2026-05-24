<?php

namespace App\Modules\SubjekGo\Models;

use App\Models\User;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
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

    public function academicSubjectOfferings(): HasMany
    {
        return $this->hasMany(AcademicSubjectOffering::class, 'academic_semester_id', 'academic_semester_id');
    }

    public function activeAcademicSubjectOfferings(): HasMany
    {
        return $this->academicSubjectOfferings()
            ->where('is_active', true)
            ->whereNull('archived_at');
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

    public function scopeCanonicalForDisplay(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereNull('subjek_go_sessions.academic_semester_id')
                ->orWhereNotExists(function ($subquery): void {
                    $statusRankSql = "FIELD(competing_sessions.status, 'open', 'draft', 'closed', 'archived')";
                    $currentRankSql = "FIELD(subjek_go_sessions.status, 'open', 'draft', 'closed', 'archived')";

                    $subquery
                        ->selectRaw('1')
                        ->from('subjek_go_sessions as competing_sessions')
                        ->whereColumn('competing_sessions.academic_semester_id', 'subjek_go_sessions.academic_semester_id')
                        ->whereNull('competing_sessions.deleted_at')
                        ->where(function ($betterSession) use ($statusRankSql, $currentRankSql): void {
                            $betterSession
                                ->whereRaw("{$statusRankSql} < {$currentRankSql}")
                                ->orWhere(function ($sameRank) use ($statusRankSql, $currentRankSql): void {
                                    $sameRank
                                        ->whereRaw("{$statusRankSql} = {$currentRankSql}")
                                        ->where(function ($newerSession): void {
                                            $newerSession
                                                ->whereColumn('competing_sessions.created_at', '>', 'subjek_go_sessions.created_at')
                                                ->orWhere(function ($sameTimestamp): void {
                                                    $sameTimestamp
                                                        ->whereColumn('competing_sessions.created_at', '=', 'subjek_go_sessions.created_at')
                                                        ->whereColumn('competing_sessions.id', '>', 'subjek_go_sessions.id');
                                                });
                                        });
                                });
                        });
                });
        });
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
