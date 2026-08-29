<?php

namespace App\Http\Controllers;

use App\Enums\StatutLivraison;
use App\Enums\TypeLivreur;
use App\Http\Controllers\Controller;
use App\Http\Requests\AffecterLivreurRequest;
use App\Http\Resources\LivraisonResource;
use App\Models\Livraison;
use Illuminate\Support\Facades\Gate;

class LivraisonController extends Controller
{
    private const ORDRE_ETAPES = [
        StatutLivraison::AFFECTEE, StatutLivraison::RECUPEREE,
        StatutLivraison::EN_ROUTE, StatutLivraison::LIVREE,
    ];

    public function index()
    {
        Gate::authorize('voir', Livraison::class);

        $query = Livraison::with(['commande', 'adresse', 'livreurEmploye.utilisateur', 'zoneLivraison']);

        if ($statut = request()->query('statut')) {
            $query->where('statut', $statut);
        }

        return LivraisonResource::collection($query->latest('created_at')->get());
    }

    public function affecter(AffecterLivreurRequest $request, string $id)
    {
        $livraison = Livraison::findOrFail($id);
        Gate::authorize('gerer', $livraison);

        $data = $request->validated();
        $type = TypeLivreur::from($data['type']);

        $livraison->update([
            'type_livreur' => $type,
            'livreur_employe_id' => $type === TypeLivreur::EMPLOYE_RESTAURANT ? $data['employe_id'] : null,
            'nom_livreur_externe' => $type !== TypeLivreur::EMPLOYE_RESTAURANT ? $data['nom'] : null,
            'telephone_livreur_externe' => $type !== TypeLivreur::EMPLOYE_RESTAURANT ? $data['telephone'] : null,
            'statut' => StatutLivraison::AFFECTEE,
            'date_affectation' => now(),
        ]);

        return new LivraisonResource($livraison->fresh(['commande', 'adresse', 'livreurEmploye.utilisateur', 'zoneLivraison']));
    }

    /** Fait avancer la livraison d'une étape ; ferme la boucle avec Commande à l'arrivée sur LIVREE. */
    public function avancerStatut(string $id)
    {
        $livraison = Livraison::with('commande')->findOrFail($id);
        Gate::authorize('gerer', $livraison);

        $index = array_search($livraison->statut, self::ORDRE_ETAPES, true);
        if ($index === false || $index === count(self::ORDRE_ETAPES) - 1) {
            return response()->json(['message' => 'Transition de statut invalide depuis cet état.'], 422);
        }

        $nouveauStatut = self::ORDRE_ETAPES[$index + 1];
        $livraison->update(['statut' => $nouveauStatut]);

        if ($nouveauStatut === StatutLivraison::LIVREE) {
            $ancienStatutCommande = $livraison->commande->statut;
            $livraison->commande->update(['statut' => 'LIVREE']);
            $livraison->commande->historique()->create([
                'ancien_statut' => $ancienStatutCommande, 'nouveau_statut' => 'LIVREE', 'date' => now(),
            ]);
        }

        return new LivraisonResource($livraison->fresh(['commande', 'adresse', 'livreurEmploye.utilisateur', 'zoneLivraison']));
    }

    public function annuler(string $id)
    {
        $livraison = Livraison::with('commande')->findOrFail($id);
        Gate::authorize('gerer', $livraison);

        $livraison->update(['statut' => StatutLivraison::ANNULEE]);

        $ancienStatutCommande = $livraison->commande->statut;
        $livraison->commande->update(['statut' => 'ANNULEE']);
        $livraison->commande->historique()->create([
            'ancien_statut' => $ancienStatutCommande, 'nouveau_statut' => 'ANNULEE', 'date' => now(),
        ]);

        return response()->json(null, 204);
    }
}