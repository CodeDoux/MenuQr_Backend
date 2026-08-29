<?php

namespace App\Http\Controllers;

use App\Enums\ModeCommande;
use App\Enums\StatutCommande;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommandeResource;
use App\Models\Commande;
use App\Services\JournalService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request as RequestFacade;

class CommandeController extends Controller
{
    private const ORDRE_STATUTS_CUISINE = [
        StatutCommande::EN_ATTENTE, StatutCommande::CONFIRMEE,
        StatutCommande::EN_PREPARATION, StatutCommande::PRETE,
    ];

    public function __construct(private readonly JournalService $journal) {}

    public function index()
    {
        Gate::authorize('voir', Commande::class);

        $query = Commande::with(['lignes.produit', 'table'])->orderByDesc('created_at');

        if ($statut = RequestFacade::query('statut')) {
            $query->where('statut', $statut);
        }
        if ($mode = RequestFacade::query('mode')) {
            $query->where('mode', $mode);
        }

        return CommandeResource::collection($query->get());
    }

    public function avancerStatutCuisine(string $id)
    {
        $commande = Commande::findOrFail($id);
        Gate::authorize('gererStatut', $commande);

        $index = array_search($commande->statut, self::ORDRE_STATUTS_CUISINE, true);
        if ($index === false || $index === count(self::ORDRE_STATUTS_CUISINE) - 1) {
            return response()->json(['message' => 'Transition de statut invalide depuis cet état.'], 422);
        }

        $ancienStatut = $commande->statut;
        $nouveauStatut = self::ORDRE_STATUTS_CUISINE[$index + 1];
        $commande->update(['statut' => $nouveauStatut]);

        $commande->historique()->create([
            'ancien_statut' => $ancienStatut, 'nouveau_statut' => $nouveauStatut,
            'utilisateur_id' => auth()->id(), 'date' => now(),
        ]);

        $this->journal->enregistrer('changement_statut_commande', 'commandes', $commande->id, $ancienStatut->value, $nouveauStatut->value);

        return new CommandeResource($commande->fresh(['lignes.produit', 'table']));
    }

    public function terminer(string $id)
    {
        $commande = Commande::findOrFail($id);
        Gate::authorize('gererStatut', $commande);

        $statutFinal = match ($commande->mode) {
            ModeCommande::SUR_PLACE => StatutCommande::SERVIE,
            ModeCommande::EMPORTER => StatutCommande::REMISE,
            ModeCommande::LIVRAISON => StatutCommande::LIVREE,
        };

        $ancienStatut = $commande->statut;
        $commande->update(['statut' => $statutFinal]);
        $commande->historique()->create([
            'ancien_statut' => $ancienStatut, 'nouveau_statut' => $statutFinal,
            'utilisateur_id' => auth()->id(), 'date' => now(),
        ]);

        $this->journal->enregistrer('changement_statut_commande', 'commandes', $commande->id, $ancienStatut->value, $statutFinal->value);

        return new CommandeResource($commande->fresh(['lignes.produit', 'table']));
    }

    public function annuler(string $id)
    {
        $commande = Commande::findOrFail($id);
        Gate::authorize('annuler', $commande);

        $ancienStatut = $commande->statut;
        $commande->update(['statut' => StatutCommande::ANNULEE]);
        $commande->historique()->create([
            'ancien_statut' => $ancienStatut, 'nouveau_statut' => StatutCommande::ANNULEE,
            'utilisateur_id' => auth()->id(), 'date' => now(),
        ]);

        $this->journal->enregistrer('changement_statut_commande', 'commandes', $commande->id, $ancienStatut->value, 'ANNULEE');

        return new CommandeResource($commande->fresh(['lignes.produit', 'table']));
    }
}