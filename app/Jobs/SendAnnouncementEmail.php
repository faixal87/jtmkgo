<?php

namespace App\Jobs;

use App\Mail\AnnouncementMail;
use App\Models\EmailLog;
use App\Models\User;
use App\Support\AnnouncementTemplates;
use App\Support\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAnnouncementEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $emailLogId,
        public int $recipientUserId,
        public string $subjectLine,
        public string $html,
    ) {}

    public function handle(MailSettings $mailSettings): void
    {
        $recipient = User::query()->find($this->recipientUserId);

        if (! $recipient?->email) {
            EmailLog::query()->find($this->emailLogId)?->markFailed('Recipient user or email no longer exists.');

            return;
        }

        // Must run before Mail::to() — the transport is built from config the
        // moment the mailer resolves.
        $mailSettings->applyToRuntimeConfig();

        $embedPhotoPath = str_contains($this->html, '{photo}')
            ? $recipient->profilePhotoStoragePath()
            : null;

        Mail::to($recipient->email)->send(new AnnouncementMail(
            AnnouncementTemplates::personalize($this->subjectLine, $recipient->name),
            AnnouncementTemplates::personalize($this->html, $recipient->name),
            $embedPhotoPath,
        ));

        EmailLog::query()->find($this->emailLogId)?->markSent();
    }

    public function failed(?Throwable $exception): void
    {
        EmailLog::query()->find($this->emailLogId)?->markFailed(
            $exception?->getMessage() ?? 'Unknown error.'
        );
    }
}
