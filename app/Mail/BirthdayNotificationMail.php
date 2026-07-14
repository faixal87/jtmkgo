<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BirthdayNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {}

    public function build(): self
    {
        return $this
            ->subject("Happy Birthday, {$this->recipient->name}!")
            ->view('emails.birthday-notification')
            ->with([
                'photoPath' => $this->recipient->profilePhotoStoragePath(),
                'initials' => $this->recipient->initials() ?: 'J',
                'actionLabel' => $this->actionLabel ?: 'View Profile',
            ]);
    }
}
