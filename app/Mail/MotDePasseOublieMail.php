<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MotDePasseOublieMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $lienReinitialisation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Réinitialisation de votre mot de passe MenuQr');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mot-de-passe-oublie',
            with: ['lien' => $this->lienReinitialisation],
        );
    }
}