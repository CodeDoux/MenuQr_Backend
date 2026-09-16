<?php

namespace App\Mail;

use App\Models\Abonnement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbonnementExpirationProcheMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Abonnement $abonnement) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre abonnement MenuQr expire dans 3 jours',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abonnement-expiration-proche',
            with: [
                'restaurantNom' => $this->abonnement->restaurant->nom,
                'offreNom' => $this->abonnement->offre->nom,
                'dateFin' => $this->abonnement->date_fin->format('d/m/Y'),
                'lienAbonnement' => config('app.frontend_url').'/abonnement',
            ],
        );
    }
}