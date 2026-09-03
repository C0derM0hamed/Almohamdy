<?php

namespace App\Mail;

use App\Models\GovAccountNotice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GovAccountNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly GovAccountNotice $notice, public readonly string $recipientName, public readonly string $viewUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            subject: $this->notice->title,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.gov-accounts.notice', text: 'emails.gov-accounts.notice-text');
    }
}
