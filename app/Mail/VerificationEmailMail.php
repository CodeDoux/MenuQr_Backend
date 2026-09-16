<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $lienVerification) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Vérifiez votre adresse email — MenuQr');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-email',
            with: ['lien' => $this->lienVerification],
        );
    }
}