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
}