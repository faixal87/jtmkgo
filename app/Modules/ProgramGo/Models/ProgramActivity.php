<?php

namespace App\Modules\ProgramGo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramActivity extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending_verification';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned_for_correction';

    public const SPEAKER_INTERNAL = 'internal';
    public const SPEAKER_EXTERNAL = 'external';
    public const SPEAKER_SUPPLIER = 'supplier';

    protected $table = 'program_go_activities';

    protected $fillable = [
        'user_id',
        'reference_no',
        'activity_name',
        'activity_code',
        'activity_code_label',
        'activity_date',
        'venue',
        'participant_count',
        'speaker_type',
        'os_21000',
        'os_29000',
        'os_42000',
        'hep_allocation',
        'subtotal_os',
        'total_budget',
        'paperwork_link',
        'implementation_report_link',
        'status',
        'admin_remarks',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    public static function activityCodes(): array
    {
        return [
            '01' => 'Pentadbiran / Pengurusan',
            '02' => 'Pengajaran dan Pembelajaran',
            '03' => 'Pembangunan dan Penyelidikan',
            '04' => 'Pembangunan Diri',
            '05' => 'Kewangan',
            '06' => 'Kepimpinan',
            '07' => 'Teknikal',
            '08' => 'Teknologi Maklumat',
            '09' => 'Lain-lain',
        ];
    }

    public static function speakerTypes(): array
    {
        return [
            self::SPEAKER_INTERNAL => 'Internal',
            self::SPEAKER_EXTERNAL => 'External',
            self::SPEAKER_SUPPLIER => 'Supplier',
        ];
    }

    public static function participantRanges(): array
    {
        return [
            10 => '1-10',
            20 => '11-20',
            30 => '21-30',
            50 => '31-50',
            100 => '51-100',
            101 => 'More than 100',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_PENDING => 'Pending Verification',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_RETURNED => 'Returned for Correction',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public static function verificationPendingStatuses(): array
    {
        return [
            self::STATUS_COMPLETED,
            self::STATUS_PENDING,
        ];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('activity_name', 'like', "%{$search}%")
                ->orWhere('reference_no', 'like', "%{$search}%")
                ->orWhere('activity_code_label', 'like', "%{$search}%")
                ->orWhereHas('lecturer', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $user->is_super_admin
            && in_array($this->status, [
                self::STATUS_DRAFT,
                self::STATUS_IN_PROGRESS,
                self::STATUS_COMPLETED,
                self::STATUS_RETURNED,
            ], true);
    }

    public function canBeDeletedBy(User $user, bool $isModuleAdmin = false): bool
    {
        if ($isModuleAdmin) {
            return true;
        }

        return $this->user_id === $user->id
            && ! $user->is_super_admin
            && in_array($this->status, [self::STATUS_DRAFT, self::STATUS_IN_PROGRESS], true);
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function speakerTypeLabel(): string
    {
        return self::speakerTypes()[$this->speaker_type] ?? str($this->speaker_type)->title()->toString();
    }

    public function participantRangeLabel(): string
    {
        return $this->participant_count !== null ? (string) $this->participant_count : 'Not set';
    }

    public function recalculatedBudget(): array
    {
        $subtotal = (float) $this->os_21000 + (float) $this->os_29000 + (float) $this->os_42000;

        return [
            'subtotal_os' => round($subtotal, 2),
            'total_budget' => round($subtotal + (float) $this->hep_allocation, 2),
        ];
    }

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'participant_count' => 'integer',
            'os_21000' => 'decimal:2',
            'os_29000' => 'decimal:2',
            'os_42000' => 'decimal:2',
            'hep_allocation' => 'decimal:2',
            'subtotal_os' => 'decimal:2',
            'total_budget' => 'decimal:2',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
