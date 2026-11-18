<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Ulasan tidak ditampilkan: alasan dari tim, dan link untuk mengubahnya. */
class ReviewRejectedMail extends Mailable
{
    use Queueable;

    public function __construct(public string $vendor, public string $reason, public string $editUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ulasanmu untuk '.$this->vendor.' belum bisa ditampilkan');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.review-rejected');
    }
}
