<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $resetUrl,
        public int $expireMinutes
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset your password - ق',
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', 'ق')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.password_reset',
            text: 'emails.auth.password_reset_text',
        );
    }
}
