<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailSettingsTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $providerLabel) {}

    public function build(): self
    {
        return $this
            ->subject('Test Email from JTMK Go!')
            ->view('emails.test');
    }
}
