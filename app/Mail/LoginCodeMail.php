<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email berisi kode masuk 6 digit. */
class LoginCodeMail extends Mailable
{
    use Queueable;

    public function __construct(public string $code, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->code.' adalah kode masuk Kamalan kamu');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.login-code', text: 'mail.login-code-text');
    }
}
