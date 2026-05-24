<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\CarbonInterface;

class EmailOtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $otp,
        public CarbonInterface $expiresAt,
        public string $purpose = 'email_verification'
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->purpose === 'password_reset'
            ? 'Reset your password - ق'
            : 'Verify your email - ق';

        return new Envelope(
            subject: $subject,
            from: new \Illuminate\Mail\Mailables\Address(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME', 'ق')),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.email_otp',
            text: 'emails.auth.email_otp_text',
        );
    }
}
