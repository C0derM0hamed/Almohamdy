<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $recipientName,
        public readonly int $expiryMinutes,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = config('mail.reply_to.address');

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            replyTo: $replyTo
                ? [new Address((string) $replyTo, (string) config('mail.from.name'))]
                : [],
            subject: __('otp.email_subject', [], 'ar'),
        );
    }

    public function content(): Content
    {
        // A plain-text alternative is required alongside the HTML part: an
        // HTML-only message carrying an embedded image scores as spam at Gmail,
        // which accepts it at SMTP level and then files it out of the inbox.
        return new Content(
            view: 'emails.auth.login-otp',
            text: 'emails.auth.login-otp-text',
        );
    }
}
