<?php

namespace App\Modules\PhotoRepository\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MediaPhoto extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'media_profile_id',
        'media_category_id',
        'photo_path',
        'thumbnail_path',
        'original_filename',
        'caption',
        'status',
        'rejection_remarks',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'is_current_official',
        'is_featured',
        'view_count',
        'download_count',
        'file_size',
        'mime_type',
        'width',
        'height',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MediaProfile::class, 'media_profile_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MediaCategory::class, 'media_category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function downloadLogs(): HasMany
    {
        return $this->hasMany(MediaDownloadLog::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('caption', 'like', "%{$search}%")
                ->orWhereHas('profile', function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('organization', 'like', "%{$search}%");
                })
                ->orWhereHas('category', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : $this->photoUrl();
    }

    public function downloadFilename(): string
    {
        $extension = pathinfo($this->photo_path, PATHINFO_EXTENSION) ?: 'webp';
        $profile = str($this->profile?->name ?? 'photo')->slug();
        $category = str($this->category?->name ?? 'portrait')->slug();

        return "{$profile}-{$category}.{$extension}";
    }

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'is_current_official' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }
}
