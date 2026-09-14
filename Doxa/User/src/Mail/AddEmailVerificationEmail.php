<?php

namespace Doxa\User\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AddEmailVerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array{code: string, code_expire_in: int, email: string} $data
     */
    public function __construct(public array $data)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: vocab('email.add_email_verification_subject'),
            from: new Address(config('mail.from.address'), config('mail.from.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: config('mail.views.add-email-verification', 'user::emails.add-email-verification'),
            with: $this->data,
        );
    }
}
