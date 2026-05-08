<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'read_at',
        'created_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function markAsRead(): void
    {
        if ($this->read_at) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }
}
