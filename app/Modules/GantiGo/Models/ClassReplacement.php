<?php

namespace App\Modules\GantiGo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassReplacement extends Model
{
    public const STATUS_PLANNED = 'planned';
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_OVERDUE = 'overdue';

    public const STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_PENDING_VERIFICATION,
        self::STATUS_VERIFIED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_OVERDUE,
    ];

    public const REPLACEMENT_METHODS = [
        'Face-to-face',
        'Online',
        'Hybrid',
        'Combined Class',
        'Others',
    ];

    protected $fillable = [
        'semester_id',
        'user_id',
        'course_id',
        'programme_id',
        'already_implemented',
        'original_class_date',
        'original_start_time',
        'original_end_time',
        'original_venue',
        'original_duration_minutes',
        'replacement_date',
        'replacement_start_time',
        'replacement_end_time',
        'replacement_duration_minutes',
        'replacement_method',
        'replacement_venue',
        'reason',
        'remarks',
        'status',
        'implementation_submitted_at',
        'implementation_approved_by',
        'implementation_approved_at',
        'implementation_rejected_by',
        'implementation_rejected_at',
        'implementation_admin_remarks',
        'evidence_path',
        'evidence_original_name',
        'evidence_uploaded_at',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassGroup::class,
            'class_replacement_classes',
            'class_replacement_id',
            'class_id'
        )->withTimestamps();
    }

    public function implementationApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'implementation_approved_by');
    }

    public function implementationRejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'implementation_rejected_by');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeSubmittedForReview(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_VERIFICATION);
    }

    public function scopePendingVerification(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_VERIFICATION);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PLANNED)
            ->whereDate('replacement_date', '>=', now()->toDateString());
    }

    public function canBeEditedByLecturer(): bool
    {
        return ! $this->isArchived()
            && in_array($this->status, [self::STATUS_PLANNED, self::STATUS_REJECTED], true);
    }

    public function canBeCancelled(): bool
    {
        return ! $this->isArchived()
            && in_array($this->status, [self::STATUS_PLANNED, self::STATUS_REJECTED], true);
    }

    public function canSubmitImplementation(): bool
    {
        return ! $this->isArchived()
            && in_array($this->status, [self::STATUS_PLANNED, self::STATUS_REJECTED], true);
    }

    public function canBeReviewed(): bool
    {
        return $this->status === self::STATUS_PENDING_VERIFICATION;
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function canBeReviewedBy(User $user): bool
    {
        return $this->canBeReviewed() && ! $this->isOwnedBy($user);
    }

    public function blocksSelfVerificationFor(User $user): bool
    {
        return $this->canBeReviewed() && $this->isOwnedBy($user);
    }

    public function isArchived(): bool
    {
        return $this->semester?->isArchived() ?? false;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PLANNED => 'Planned',
            self::STATUS_PENDING_VERIFICATION => 'Pending Verification',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_OVERDUE => 'Overdue',
            default => 'Unknown',
        };
    }

    public function formattedClassGroups(): string
    {
        $classNames = $this->classes->pluck('class_name')->filter()->values();

        return $classNames->isNotEmpty()
            ? $classNames->join(', ')
            : ($this->course?->class_name ?: 'Not assigned');
    }

    public function formattedDuration(?int $minutes): string
    {
        if (! $minutes) {
            return 'Not calculated';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return trim(($hours ? "{$hours}h " : '').($remaining ? "{$remaining}m" : ''));
    }

    protected function casts(): array
    {
        return [
            'already_implemented' => 'boolean',
            'original_class_date' => 'date',
            'replacement_date' => 'date',
            'implementation_submitted_at' => 'datetime',
            'implementation_approved_at' => 'datetime',
            'implementation_rejected_at' => 'datetime',
            'evidence_uploaded_at' => 'datetime',
        ];
    }
}
