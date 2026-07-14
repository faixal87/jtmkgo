<?php

namespace App\Jobs;

use App\Mail\BirthdayNotificationMail;
use App\Mail\GeneralNotificationMail;
use App\Models\EmailLog;
use App\Support\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $emailLogId,
        public string $recipientEmail,
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $type = null,
    ) {}

    public function handle(MailSettings $mailSettings): void
    {
        // Must run before Mail::to() — the transport is built from config the
        // moment the mailer resolves, and the worker never shares the web
        // request's runtime config.
        $mailSettings->applyToRuntimeConfig();

        $log = EmailLog::query()->with('user')->find($this->emailLogId);
        $mailable = str_starts_with((string) $this->type, 'birthday:') && $log?->user
            ? new BirthdayNotificationMail($log->user, $this->actionUrl, $this->actionLabel)
            : new GeneralNotificationMail($this->title, $this->body, $this->actionUrl, $this->actionLabel, $this->type);

        Mail::to($this->recipientEmail)->send($mailable);

        $log?->markSent();
    }

    public function failed(?Throwable $exception): void
    {
        EmailLog::query()->find($this->emailLogId)?->markFailed(
            $exception?->getMessage() ?? 'Unknown error.'
        );
    }
}
