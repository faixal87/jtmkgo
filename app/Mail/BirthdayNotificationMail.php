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
        $displayName = $this->displayName();

        return $this
            ->subject("Happy Birthday, {$displayName}!")
            ->view('emails.birthday-notification')
            ->with([
                'displayName' => $displayName,
                'photoPath' => $this->recipient->profilePhotoStoragePath(),
                'initials' => $this->initialsFrom($displayName),
            ]);
    }

    private function displayName(): string
    {
        $name = trim((string) preg_replace('/\s+/', ' ', $this->recipient->name));

        $name = (string) preg_replace(
            '/^(?:(?:PROF\.?\s*MADYA|ASSOC\.?\s*PROF|TS|IR|DR|PROF|DATUK|DATO|TUAN|PUAN|ENCIK|CIK)\.?\s+)+/i',
            '',
            $name
        );

        $name = (string) preg_replace('/\s+(?:BIN|BINTI|BT|BTE|B\.)\s+.*$/i', '', $name);
        $name = trim($name);

        return $name !== '' ? $name : $this->recipient->name;
    }

    private function initialsFrom(string $name): string
    {
        $initials = collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'J';
    }
}
