<?php

namespace App\Modules\SurveyGo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    public const TYPE_BASELINE = 'baseline';
    public const TYPE_IMPACT = 'impact';
    public const TYPE_MODULE_SPECIFIC = 'module_specific';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'survey_go_surveys';

    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'is_forced',
        'is_notification_sent',
        'launched_at',
        'closed_at',
        'created_by',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_BASELINE => 'Kajian Awal',
            self::TYPE_IMPACT => 'Kajian Impak',
            self::TYPE_MODULE_SPECIFIC => 'Module Specific',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'survey_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeQuestions(): HasMany
    {
        return $this->questions()->where('is_active', true);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'survey_id');
    }

    public function submittedResponses(): HasMany
    {
        return $this->responses()->whereNotNull('submitted_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeBaseline(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BASELINE);
    }

    public function scopeImpact(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_IMPACT);
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? str($this->type)->replace('_', ' ')->title()->toString();
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function displayTitle(): string
    {
        return match ($this->title) {
            'JTMK Go Baseline Survey' => 'Kajian Awal JTMK Go',
            'JTMK Go Impact Survey' => 'Kajian Impak JTMK Go',
            default => $this->title,
        };
    }

    public function respondentMessage(?User $user = null): string
    {
        $name = $user?->name ?: 'warga JTMK';

        return match ($this->title) {
            'JTMK Go Baseline Survey' => "Salam {$name}, mohon luangkan sedikit masa untuk melengkapkan Kajian Awal ini sebelum menggunakan JTMK Go. Maklum balas ini membantu pasukan pembangun menyediakan sistem yang lebih baik untuk kegunaan JTMK serta menyokong usaha menaik taraf Jabatan Teknologi Maklumat sebagai jabatan digital yang lebih tersusun, inovatif dan berimpak tinggi.",
            'JTMK Go Impact Survey' => "Salam {$name}, mohon kongsikan maklum balas anda selepas menggunakan JTMK Go. Pandangan anda membantu pasukan pembangun menambah baik sistem supaya lebih bermanfaat untuk warga JTMK dan mengukuhkan imej Jabatan Teknologi Maklumat sebagai jabatan digital yang progresif dan berimpak tinggi.",
            default => $this->description ?: "Salam {$name}, mohon lengkapkan kajian ini. Maklum balas anda amat membantu penambahbaikan JTMK Go.",
        };
    }

    public function isOpenForResponses(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    protected function casts(): array
    {
        return [
            'is_forced' => 'boolean',
            'is_notification_sent' => 'boolean',
            'launched_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
