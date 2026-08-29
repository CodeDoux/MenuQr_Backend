<?php

namespace App\Http\Controllers;

use App\Enums\StatutAddition;
use App\Enums\StatutFacture;
use App\Enums\StatutPaiement;
use App\Enums\TypePaiement;
use App\Http\Controllers\Controller;
use App\Http\Requests\EncaisserRequest;
use App\Http\Resources\AdditionResource;
use App\Http\Resources\FactureResource;
use App\Http\Resources\PaiementResource;
use App\Models\Addition;
use App\Models\Commande;
use App\Models\Facture;
use App\Models\Paiement;
use App\Services\JournalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PaiementController extends Controller
{
    public function __construct(private readonly JournalService $journal) {}

    public function additionsOuvertes()
    {
        Gate::authorize('voir', Commande::class);

        $additions = Addition::where('statut', StatutAddition::OUVERTE)->with(['visite.table'])->get();

        return AdditionResource::collection($additions);
    }

    public function encaisserAddition(EncaisserRequest $request, string $id)
    {
        Gate::authorize('encaisser', Commande::class);

        $addition = Addition::findOrFail($id);

        $paiement = DB::transaction(function () use ($addition, $request) {
            $paiement = Paiement::create([
                'type' => TypePaiement::COMMANDE, 'addition_id' => $addition->id,
                'montant' => $addition->total, 'devise' => 'FCFA',
                'methode' => $request->validated('methode'), 'statut' => StatutPaiement::CONFIRME,
                'date_paiement' => now(),
            ]);

            $addition->update(['statut' => StatutAddition::PAYEE]);

            Facture::create([
                'numero' => 'FAC-'.now()->format('YmdHis'), 'addition_id' => $addition->id,
                'montant_ht' => $addition->total, 'taxe' => 0, 'montant_ttc' => $addition->total,
                'date_emission' => now(), 'statut' => StatutFacture::PAYEE,
            ]);

            return $paiement;
        });

        $this->journal->enregistrer('paiement', 'paiements', $paiement->id, null, ['type' => 'addition', 'montant' => $paiement->montant]);

        return new PaiementResource($paiement);
    }

    public function encaisserCommandeDirecte(EncaisserRequest $request, string $id)
    {
        Gate::authorize('encaisser', Commande::class);

        $commande = Commande::findOrFail($id);

        $paiement = DB::transaction(function () use ($commande, $request) {
            $paiement = Paiement::create([
                'type' => TypePaiement::COMMANDE, 'commande_id' => $commande->id,
                'montant' => $commande->total, 'devise' => 'FCFA',
                'methode' => $request->validated('methode'), 'statut' => StatutPaiement::CONFIRME,
                'date_paiement' => now(),
            ]);

            Facture::create([
                'numero' => 'FAC-'.now()->format('YmdHis'), 'commande_id' => $commande->id,
                'montant_ht' => $commande->total, 'taxe' => 0, 'montant_ttc' => $commande->total,
                'date_emission' => now(), 'statut' => StatutFacture::PAYEE,
            ]);

            return $paiement;
        });

        $this->journal->enregistrer('paiement', 'paiements', $paiement->id, null, ['type' => 'commande', 'montant' => $paiement->montant]);

        return new PaiementResource($paiement);
    }

    public function index()
    {
        Gate::authorize('consulterFactures', Commande::class);

        return PaiementResource::collection(Paiement::orderByDesc('date_paiement')->get());
    }

    public function rembourser(string $id)
    {
        Gate::authorize('rembourser', Commande::class);

        $paiement = Paiement::findOrFail($id);
        $ancienStatut = $paiement->statut;
        $paiement->update(['statut' => StatutPaiement::REMBOURSE]);

        $this->journal->enregistrer('remboursement', 'paiements', $paiement->id, $ancienStatut->value, StatutPaiement::REMBOURSE->value);

        return new PaiementResource($paiement);
    }

    public function factures()
    {
        Gate::authorize('consulterFactures', Commande::class);

        return FactureResource::collection(Facture::orderByDesc('date_emission')->get());
    }

    public function facture(string $id)
    {
        Gate::authorize('consulterFactures', Commande::class);

        $facture = Facture::with(['commande.lignes.produit', 'addition.visite.commandes.lignes.produit'])->findOrFail($id);

        return new FactureResource($facture);
    }
}