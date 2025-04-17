<?php

namespace Doxa\User\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;

class VerificationEmail extends Mailable {

    use Queueable, SerializesModels;


    public function __construct(public array $data) {
        //dump(__METHOD__, $data, env('MAIL_LOG_CHANNEL'));
    }

    public function envelope(): Envelope {
        return new Envelope(
            subject: 'Verification Email',
            from: new Address(config("mail.from.address"), config("mail.from.name")),
        );
    }

    public function content(): Content {
        return new Content(
            markdown: 'user::emails.verification-email',
            with: $this->data,
        );
    }
}
