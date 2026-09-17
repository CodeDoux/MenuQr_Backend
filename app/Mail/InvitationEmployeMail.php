<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationEmployeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomComplet,
        public string $restaurantNom,
        public string $roleLabel,
        public string $lienInvitation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Invitation à rejoindre {$this->restaurantNom} sur MenuQr");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation-employe',
            with: [
                'nomComplet' => $this->nomComplet,
                'restaurantNom' => $this->restaurantNom,
                'roleLabel' => $this->roleLabel,
                'lien' => $this->lienInvitation,
            ],
        );
    }
}