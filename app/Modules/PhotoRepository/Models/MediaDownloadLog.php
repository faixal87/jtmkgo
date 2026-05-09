<?php

namespace App\Modules\PhotoRepository\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaDownloadLog extends Model
{
    protected $fillable = [
        'media_photo_id',
        'user_id',
        'ip_address',
        'user_agent',
        'downloaded_at',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaPhoto::class, 'media_photo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }
}
