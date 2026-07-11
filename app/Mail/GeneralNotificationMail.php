<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GeneralNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $type = null,
    ) {}

    public function build(): self
    {
        $subject = $this->type === 'manual'
            ? "New Announcement: {$this->title}"
            : $this->title;

        return $this
            ->subject($subject)
            ->view('emails.notification')
            ->with([
                'isAnnouncement' => $this->type === 'manual',
            ]);
    }
}
