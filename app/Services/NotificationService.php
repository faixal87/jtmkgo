<?php

namespace App\Services;

use App\Jobs\SendNotificationEmail;
use App\Models\EmailLog;
use App\Models\Module;
use App\Models\Notification;
use App\Models\User;
use App\Support\MailSettings;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NotificationService
{
    /**
     * Maximum notification emails dispatched per minute during a blast.
     */
    private const EMAILS_PER_MINUTE = 30;

    public function __construct(private readonly MailSettings $mailSettings) {}

    public function send(
        User $recipient,
        string $title,
        string $message,
        ?string $type = null,
        ?User $createdBy = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null
    ): Notification {
        $attributes = [
            'user_id' => $recipient->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'created_by' => $createdBy?->id,
        ];

        if ($this->hasActionColumns()) {
            $attributes['action_url'] = $actionUrl;
            $attributes['action_label'] = $actionLabel;
        }

        $notification = Notification::query()->create($attributes);

        Cache::forget("notifications.unread-count.{$recipient->id}");

        $this->dispatchEmail($recipient, $title, $message, $type, $actionUrl, $actionLabel);

        return $notification;
    }

    /**
     * @param  iterable<int, User>|Collection<int, User>|EloquentCollection<int, User>  $recipients
     */
    public function sendToUsers(
        iterable $recipients,
        string $title,
        string $message,
        ?string $type = null,
        ?User $createdBy = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null
    ): int {
        $hasActionColumns = $this->hasActionColumns();

        $rows = collect($recipients)
            ->unique('id')
            ->map(function (User $recipient) use ($title, $message, $type, $createdBy, $actionUrl, $actionLabel, $hasActionColumns): array {
                $row = [
                    'user_id' => $recipient->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'created_by' => $createdBy?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($hasActionColumns) {
                    $row['action_url'] = $actionUrl;
                    $row['action_label'] = $actionLabel;
                }

                return $row;
            })
            ->values();

        $rows
            ->chunk(500)
            ->each(fn (Collection $chunk) => Notification::query()->insert($chunk->all()));

        $userIds = $rows->pluck('user_id')->unique();

        foreach ($userIds as $userId) {
            Cache::forget("notifications.unread-count.{$userId}");
        }

        if ($this->mailSettings->isEnabled()) {
            User::query()
                ->whereIn('id', $userIds)
                ->whereNotNull('email')
                ->get(['id', 'email'])
                // Blast throttle: batches of 30 recipients, one batch per minute.
                ->each(fn (User $recipient, int $index) => $this->dispatchEmail(
                    $recipient,
                    $title,
                    $message,
                    $type,
                    $actionUrl,
                    $actionLabel,
                    delaySeconds: intdiv($index, self::EMAILS_PER_MINUTE) * 60
                ));
        }

        return $rows->count();
    }

    public function sendToSuperAdmins(
        string $title,
        string $message,
        ?string $type = null,
        ?User $createdBy = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null
    ): int {
        return $this->sendToUsers(
            $this->superAdminRecipients()->get(),
            $title,
            $message,
            $type,
            $createdBy,
            $actionUrl,
            $actionLabel
        );
    }

    public function sendToModuleReviewers(
        Module $module,
        string $title,
        string $message,
        ?string $type = null,
        ?User $createdBy = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null
    ): int {
        $recipients = User::query()
            ->where('account_status', 'approved')
            ->where(function ($query) use ($module): void {
                $query
                    ->where('is_super_admin', true)
                    ->orWhereHas('adminModules', function ($query) use ($module): void {
                        $query
                            ->where('modules.id', $module->id)
                            ->wherePivot('is_active', true);
                    });
            })
            ->get();

        return $this->sendToUsers($recipients, $title, $message, $type, $createdBy, $actionUrl, $actionLabel);
    }

    public function sendToModuleAdminsBySlug(
        string $moduleSlug,
        string $title,
        string $message,
        ?string $type = null,
        ?User $createdBy = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?User $excludeUser = null
    ): int {
        $recipients = User::query()
            ->where('account_status', 'approved')
            ->where('is_super_admin', false)
            ->when($excludeUser, fn ($query) => $query->whereKeyNot($excludeUser->id))
            ->whereHas('adminModules', function ($query) use ($moduleSlug): void {
                $query
                    ->where('modules.slug', $moduleSlug)
                    ->where('module_admins.is_active', true);
            })
            ->get(['id', 'name', 'email']);

        return $this->sendToUsers($recipients, $title, $message, $type, $createdBy, $actionUrl, $actionLabel);
    }

    public function sendBirthdayNotifications(): int
    {
        $today = now();
        $type = 'birthday:'.$today->toDateString();
        $sent = 0;
        $birthdayUsers = collect();

        User::query()
            ->where('account_status', 'approved')
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->chunkById(100, function ($users) use ($type, &$sent, $birthdayUsers) {
                foreach ($users as $user) {
                    $birthdayUsers->push($user);

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
                        $type,
                        actionUrl: route('staff-directory.index', ['user_id' => $user->id]),
                        actionLabel: 'View Profile'
                    );

                    $sent++;
                }
            });

        $adminBirthdayType = $type.':admin';

        if ($birthdayUsers->isNotEmpty() && ! Notification::query()->where('type', $adminBirthdayType)->exists()) {
            $names = $birthdayUsers
                ->pluck('name')
                ->take(8)
                ->implode(', ');
            $extra = $birthdayUsers->count() > 8 ? ' and '.($birthdayUsers->count() - 8).' more' : '';

            $this->sendToSuperAdmins(
                'Today\'s Staff Birthday',
                "Birthday today: {$names}{$extra}.",
                $adminBirthdayType,
                actionUrl: route('staff-directory.index', ['q' => $birthdayUsers->first()->name]),
                actionLabel: 'Open Staff Directory'
            );
        }

        return $sent;
    }

    private function superAdminRecipients()
    {
        return User::query()
            ->where('account_status', 'approved')
            ->where('is_super_admin', true);
    }

    private function hasActionColumns(): bool
    {
        static $hasColumns = null;

        return $hasColumns ??= Schema::hasTable('notifications')
            && Schema::hasColumn('notifications', 'action_url')
            && Schema::hasColumn('notifications', 'action_label');
    }

    private function dispatchEmail(
        User $recipient,
        string $title,
        string $message,
        ?string $type,
        ?string $actionUrl,
        ?string $actionLabel,
        int $delaySeconds = 0
    ): void {
        if (! $this->mailSettings->isEnabled() || ! $recipient->email) {
            return;
        }

        try {
            $log = EmailLog::query()->create([
                'user_id' => $recipient->id,
                'recipient_email' => $recipient->email,
                'subject' => $title,
                'type' => $type,
                'status' => EmailLog::STATUS_QUEUED,
            ]);

            SendNotificationEmail::dispatch(
                $log->id,
                $recipient->email,
                $title,
                $message,
                $actionUrl,
                $actionLabel,
                $type
            )->delay($delaySeconds > 0 ? now()->addSeconds($delaySeconds) : null);
        } catch (Throwable $e) {
            Log::warning('Failed to queue notification email.', [
                'user_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
