<?php

namespace App\Console\Commands;

use App\Mail\AbonnementExpirationProcheMail;
use App\Models\Abonnement;
use App\Models\Notification;
use App\Models\RestaurantUtilisateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Tâche planifiée quotidienne (voir routes/console.php) : prévient chaque
 * restaurant dont l'abonnement expire dans exactement 3 jours — à la fois
 * par notification in-app (Propriétaire + Gérant) et par email (Propriétaire
 * uniquement). Compense volontairement l'absence de renouvellement
 * automatique (décision actée : le mobile money ne permet pas de vrai
 * prélèvement récurrent sans action du client).
 */
class NotifierAbonnementsExpirants extends Command
{
    protected $signature = 'abonnements:notifier-expirations';

    protected $description = 'Notifie les restaurants dont l\'abonnement expire dans 3 jours';

    public function handle(): int
    {
        $dansTroisJours = now()->addDays(3)->toDateString();

        $abonnements = Abonnement::with(['restaurant', 'offre'])
            ->whereIn('statut', ['ACTIF', 'ESSAI'])
            ->whereDate('date_fin', $dansTroisJours)
            ->get();

        foreach ($abonnements as $abonnement) {
            $this->notifierRestaurant($abonnement);
        }

        $this->info("{$abonnements->count()} restaurant(s) notifié(s).");

        return self::SUCCESS;
    }

    private function notifierRestaurant(Abonnement $abonnement): void
    {
        $managers = RestaurantUtilisateur::where('restaurant_id', $abonnement->restaurant_id)
            ->where('statut', 'ACTIF')
            ->whereHas('role', fn ($q) => $q->whereIn('code', ['PROPRIETAIRE', 'GERANT']))
            ->with(['utilisateur', 'role'])
            ->get();

        foreach ($managers as $acces) {
            Notification::create([
                'utilisateur_id' => $acces->utilisateur_id,
                'titre' => 'Abonnement bientôt expiré',
                'message' => "Votre abonnement \"{$abonnement->offre->nom}\" expire dans 3 jours. Pensez à le renouveler.",
                'type' => 'ABONNEMENT_EXPIRATION',
                'lien' => '/abonnement',
                'est_lu' => false,
                'date_envoie' => now(),
            ]);
        }

        // Email uniquement au Propriétaire (pas à chaque Gérant, pour ne pas spammer).
        $proprietaire = $managers->first(fn ($acces) => $acces->role->code === 'PROPRIETAIRE');

        if ($proprietaire) {
            Mail::to($proprietaire->utilisateur->email)
                ->send(new AbonnementExpirationProcheMail($abonnement));
        }
    }
}