<?php

namespace Doxa\User\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Письмо с кодом 2FA / подтверждения ящика (бренд и SMTP — текущего домена).
 */
class TwoFactorCodeEmail extends Mailable
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
            subject: vocab('email.two_factor_code_subject'),
            from: new Address(config('mail.from.address'), config('mail.from.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: config('mail.views.two-factor-code', 'user::emails.two-factor-code'),
            with: $this->data,
        );
    }
}
