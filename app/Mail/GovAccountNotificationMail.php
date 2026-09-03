<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GovAccountNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $recipientName, public readonly string $subjectLine, public readonly string $messageText, public readonly string $actionUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')), subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.gov-accounts.notification', text: 'emails.gov-accounts.notification-text');
    }
}
