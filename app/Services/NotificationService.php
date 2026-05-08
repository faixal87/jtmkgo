<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class NotificationService
{
    public function send(User $recipient, string $title, string $message, ?string $type = null, ?User $createdBy = null): Notification
    {
        $notification = Notification::query()->create([
            'user_id' => $recipient->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'created_by' => $createdBy?->id,
        ]);

        Cache::forget("notifications.unread-count.{$recipient->id}");

        return $notification;
    }

    /**
     * @param iterable<int, User>|Collection<int, User>|EloquentCollection<int, User> $recipients
     */
    public function sendToUsers(iterable $recipients, string $title, string $message, ?string $type = null, ?User $createdBy = null): int
    {
        $rows = collect($recipients)
            ->unique('id')
            ->map(fn (User $recipient) => [
                'user_id' => $recipient->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'created_by' => $createdBy?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values();

        $rows
            ->chunk(500)
            ->each(fn (Collection $chunk) => Notification::query()->insert($chunk->all()));

        foreach ($rows->pluck('user_id')->unique() as $userId) {
            Cache::forget("notifications.unread-count.{$userId}");
        }

        return $rows->count();
    }

    public function sendBirthdayNotifications(): int
    {
        $today = now();
        $type = 'birthday:'.$today->toDateString();
        $sent = 0;

        User::query()
            ->where('account_status', 'approved')
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->chunkById(100, function ($users) use ($type, &$sent) {
                foreach ($users as $user) {
                    $alreadySent = Notification::query()
                        ->where('user_id', $user->id)
                        ->where('type', $type)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $this->send(
                        $user,
                        'Happy Birthday JTMK Stars!',
                        'Happy Birthday JTMK Stars! Have a Blast!',
                        $type
                    );

                    $sent++;
                }
            });

        return $sent;
    }
}
