<?php

namespace App\Http\Controllers;

use App\Enums\StatutAbonnement;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangerOffreRequest;
use App\Http\Resources\AbonnementResource;
use App\Http\Resources\FactureAbonnementResource;
use App\Models\Abonnement;
use App\Models\FactureAbonnement;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Gate;
use App\Services\PaydunyaService;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;

class AbonnementController extends Controller
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function show()
    {
        $abonnement = Abonnement::with('offre')
            ->where('restaurant_id', $this->tenant->restaurantId)
            ->latest('date_debut')
            ->firstOrFail();

        return new AbonnementResource($abonnement);
    }

    /** Changement de plan. ⚠️ Simplification : la date de fin en cours n'est
     *  pas recalculée (pas de logique de proratisation) — le nouveau tarif
     *  s'appliquera à la prochaine facturation. Pas d'intégration de paiement
     *  réel pour l'instant (à faire lors d'un module de paiement d'abonnement dédié). */
    public function changerOffre(ChangerOffreRequest $request)
    {
        $abonnement = Abonnement::where('restaurant_id', $this->tenant->restaurantId)->latest('date_debut')->firstOrFail();
        $this->autoriserGestion();

        $abonnement->update(['offre_id' => $request->validated('offre_id')]);

        return new AbonnementResource($abonnement->fresh('offre'));
    }

    public function annuler()
    {
        $abonnement = Abonnement::where('restaurant_id', $this->tenant->restaurantId)->latest('date_debut')->firstOrFail();
        $this->autoriserGestion();

        $abonnement->update(['statut' => StatutAbonnement::ANNULE, 'renouvellement_automatique' => false]);

        return new AbonnementResource($abonnement->fresh('offre'));
    }

    public function reactiver()
    {
        $abonnement = Abonnement::where('restaurant_id', $this->tenant->restaurantId)->latest('date_debut')->firstOrFail();
        $this->autoriserGestion();

        if ($abonnement->statut !== StatutAbonnement::ANNULE) {
            return response()->json(['message' => 'Cet abonnement n\'est pas annulé.'], 422);
        }

        $abonnement->update(['statut' => StatutAbonnement::ACTIF]);

        return new AbonnementResource($abonnement->fresh('offre'));
    }

    public function factures()
    {
        $abonnement = Abonnement::where('restaurant_id', $this->tenant->restaurantId)->latest('date_debut')->firstOrFail();
        $this->autoriserGestion();

        $factures = FactureAbonnement::where('abonnement_id', $abonnement->id)->orderByDesc('date_emission')->get();

        return FactureAbonnementResource::collection($factures);
    }

    private function autoriserGestion(): void
    {
        if (! $this->tenant->aLaPermission('abonnement.gerer')) {
            abort(403, 'Action réservée au Propriétaire.');
        }
    }

    /** Démarre un paiement PayDunya pour renouveler l'abonnement du restaurant
 *  courant. Crée la Facture (EN_ATTENTE) et le Paiement (EN_ATTENTE) liés,
 *  puis renvoie l'URL PayDunya vers laquelle rediriger le restaurateur. */
public function payer(PaydunyaService $paydunya)
{
    $this->autoriserGestion();

    $abonnement = Abonnement::with('offre')
        ->where('restaurant_id', $this->tenant->restaurantId)
        ->latest('date_debut')->firstOrFail();
    $offre = $abonnement->offre;

    [$facture, $paiement] = DB::transaction(function () use ($abonnement, $offre) {
        $facture = FactureAbonnement::create([
            'abonnement_id' => $abonnement->id,
            'numero' => 'FACAB-'.now()->format('YmdHis'),
            'montant' => $offre->prix_mensuel,
            'date_emission' => now(),
            'date_echeance' => now()->addDays(7),
            'statut' => 'EN_ATTENTE',
        ]);

        $paiement = Paiement::create([
            'type' => 'ABONNEMENT',
            'facture_abonnement_id' => $facture->id,
            'montant' => $offre->prix_mensuel,
            'devise' => 'FCFA',
            'methode' => 'AUTRE', // ⚠️ inconnu tant que le client n'a pas choisi sur PayDunya
            'statut' => 'EN_ATTENTE',
        ]);

        return [$facture, $paiement];
    });

    try {
        $resultat = $paydunya->creerFacture(
            (float) $offre->prix_mensuel,
            "Abonnement MenuQr — {$offre->nom}",
            config('app.frontend_url').'/abonnement?paiement=retour',
            config('app.url').'/api/public/paydunya/webhook'
        );
    } catch (\RuntimeException $e) {
        $paiement->update(['statut' => 'ECHOUE']);
        return response()->json(['message' => $e->getMessage()], 422);
    }

    $paiement->update(['reference' => $resultat['token']]);

    return response()->json(['url_paiement' => $resultat['url']]);
}
}