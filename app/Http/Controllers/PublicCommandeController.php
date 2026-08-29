<?php

namespace App\Http\Controllers;

use App\Enums\StatutAddition;
use App\Enums\StatutCommande;
use App\Enums\StatutFacture;
use App\Enums\StatutPaiement;
use App\Enums\StatutVisite;
use App\Enums\TypePaiement;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreerCommandePubliqueRequest;
use App\Http\Resources\CommandeResource;
use App\Models\Addition;
use App\Models\Commande;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\Produit;
use App\Models\QRCode;
use App\Models\Scopes\RestaurantScope;
use App\Models\Variante;
use App\Models\Visite;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️ Routes publiques. Le restaurant/table sont TOUJOURS résolus via le
 * "code" du QR (jamais via un id envoyé par le client). Les prix des
 * lignes sont TOUJOURS recalculés depuis Produit/Variante en base — jamais
 * un prix envoyé par le client (protection contre la manipulation de prix).
 */
class PublicCommandeController extends Controller
{
    public function store(CreerCommandePubliqueRequest $request)
    {
        $data = $request->validated();

        $qrCode = QRCode::withoutGlobalScope(RestaurantScope::class)
            ->where('code', $data['code'])->where('est_actif', true)->first();

        if (!$qrCode) {
            return response()->json(['message' => 'QR code invalide ou expiré.'], 404);
        }

        $commande = DB::transaction(function () use ($qrCode, $data) {
            $lignes = [];
            $sousTotal = 0;

            foreach ($data['items'] as $item) {
                $produit = Produit::withoutGlobalScope(RestaurantScope::class)
                    ->where('id', $item['produit_id'])
                    ->where('restaurant_id', $qrCode->restaurant_id)
                    ->firstOrFail();

                $prixUnitaire = $produit->prix;
                $nom = $produit->nom;

                if (!empty($item['variante_id'])) {
                    $variante = Variante::where('id', $item['variante_id'])
                        ->where('produit_id', $produit->id)->firstOrFail();
                    $prixUnitaire = $variante->prix;
                    $nom .= " ({$variante->nom})";
                }

                $sousLigneTotal = $prixUnitaire * $item['quantite'];
                $sousTotal += $sousLigneTotal;

                $lignes[] = [
                    'produit_id' => $produit->id,
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $prixUnitaire,
                    'sous_total' => $sousLigneTotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $visiteId = null;
            $tableId = null;

            if ($qrCode->table_id) {
                $visite = Visite::withoutGlobalScope(RestaurantScope::class)
                    ->where('table_id', $qrCode->table_id)
                    ->where('statut', StatutVisite::EN_COURS)
                    ->first();

                if (!$visite) {
                    $visite = Visite::withoutGlobalScope(RestaurantScope::class)->create([
                        'restaurant_id' => $qrCode->restaurant_id,
                        'table_id' => $qrCode->table_id,
                        'date_debut' => now(),
                        'statut' => StatutVisite::EN_COURS,
                    ]);
                }
                $visiteId = $visite->id;
                if ($data['mode'] === 'SUR_PLACE') {
                    $tableId = $qrCode->table_id;
                }
            }

            $fraisLivraison = 0;
            $zone = null;
            if ($data['mode'] === 'LIVRAISON') {
                $zone = \App\Models\ZoneLivraison::withoutGlobalScope(RestaurantScope::class)
                    ->where('id', $data['zone_livraison_id'])
                    ->where('restaurant_id', $qrCode->restaurant_id)
                    ->firstOrFail();
                $fraisLivraison = $zone->frais;
            }

            $commande = Commande::withoutGlobalScope(RestaurantScope::class)->create([
                'restaurant_id' => $qrCode->restaurant_id,
                'visite_id' => $visiteId,
                'table_id' => $tableId,
                'mode' => $data['mode'],
                'statut' => StatutCommande::EN_ATTENTE,
                'sous_total' => $sousTotal,
                'frais_livraison' => $fraisLivraison,
                'remise' => 0,
                'total' => $sousTotal + $fraisLivraison,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lignes as $ligne) {
                $commande->lignes()->create($ligne);
            }

            if ($data['mode'] === 'LIVRAISON') {
                $adresse = \App\Models\AdresseLivraison::create([
                    'client_id' => null,
                    'adresse_complete' => $data['adresse_complete'],
                    'quartier' => $data['quartier'] ?? null,
                    'indications' => $data['indications'] ?? null,
                ]);

                \App\Models\Livraison::create([
                    'commande_id' => $commande->id,
                    'adresse_id' => $adresse->id,
                    'nom_client' => $data['nom_client'],
                    'telephone_client' => $data['telephone_client'],
                    'type_livreur' => null,
                    'statut' => 'EN_ATTENTE_AFFECTATION',
                    'zone_livraison_id' => $zone->id,
                ]);
            }

            $commande->historique()->create([
                'ancien_statut' => null, 'nouveau_statut' => StatutCommande::EN_ATTENTE, 'date' => now(),
            ]);

            if ($visiteId) {
                $this->recalculerAddition($visiteId);
            }

            return $commande;
        });

        return new CommandeResource($commande->load(['lignes.produit' => fn ($q) => $q->withoutGlobalScope(RestaurantScope::class)]));
    }

    public function show(string $id)
    {
        $commande = Commande::withoutGlobalScope(RestaurantScope::class)
            ->with(['lignes.produit' => fn ($q) => $q->withoutGlobalScope(RestaurantScope::class), 'table'])->findOrFail($id);

        return new CommandeResource($commande);
    }

    /** Récupère les autres commandes de la même visite (RM08). */
    public function commandesDeLaVisite(string $commandeId)
    {
        $commande = Commande::withoutGlobalScope(RestaurantScope::class)->findOrFail($commandeId);
        if (!$commande->visite_id) {
            return CommandeResource::collection(collect([$commande]));
        }

        $commandes = Commande::withoutGlobalScope(RestaurantScope::class)
            ->where('visite_id', $commande->visite_id)
            ->with(['lignes.produit' => fn ($q) => $q->withoutGlobalScope(RestaurantScope::class)])
            ->get();

        return CommandeResource::collection($commandes);
    }

    /** Paiement en ligne côté client (addition de visite OU commande directe). */
    public function payer(string $commandeId)
    {
        $commande = Commande::withoutGlobalScope(RestaurantScope::class)->findOrFail($commandeId);
        $methode = request()->validate(['methode' => ['required', 'in:WAVE,ORANGE_MONEY,CARTE']])['methode'];

        $paiement = DB::transaction(function () use ($commande, $methode) {
            if ($commande->visite_id) {
                $addition = Addition::where('visite_id', $commande->visite_id)
                    ->where('statut', StatutAddition::OUVERTE)->firstOrFail();

                $paiement = Paiement::create([
                    'type' => TypePaiement::COMMANDE, 'addition_id' => $addition->id,
                    'montant' => $addition->total, 'devise' => 'FCFA', 'methode' => $methode,
                    'statut' => StatutPaiement::CONFIRME, 'date_paiement' => now(),
                ]);
                $addition->update(['statut' => StatutAddition::PAYEE]);
                Facture::create([
                    'numero' => 'FAC-'.now()->format('YmdHis'), 'addition_id' => $addition->id,
                    'montant_ht' => $addition->total, 'taxe' => 0, 'montant_ttc' => $addition->total,
                    'date_emission' => now(), 'statut' => StatutFacture::PAYEE,
                ]);
            } else {
                $paiement = Paiement::create([
                    'type' => TypePaiement::COMMANDE, 'commande_id' => $commande->id,
                    'montant' => $commande->total, 'devise' => 'FCFA', 'methode' => $methode,
                    'statut' => StatutPaiement::CONFIRME, 'date_paiement' => now(),
                ]);
                Facture::create([
                    'numero' => 'FAC-'.now()->format('YmdHis'), 'commande_id' => $commande->id,
                    'montant_ht' => $commande->total, 'taxe' => 0, 'montant_ttc' => $commande->total,
                    'date_emission' => now(), 'statut' => StatutFacture::PAYEE,
                ]);
            }
            return $paiement;
        });

        return response()->json(['message' => 'Paiement confirmé.', 'paiement_id' => $paiement->id]);
    }

    private function recalculerAddition(string $visiteId): void
    {
        $commandes = Commande::withoutGlobalScope(RestaurantScope::class)
            ->where('visite_id', $visiteId)
            ->where('statut', '!=', StatutCommande::ANNULEE)
            ->with('lignes')->get();

        $sousTotal = $commandes->flatMap->lignes->sum('sous_total');
        $fraisLivraison = $commandes->sum('frais_livraison');
        $total = $sousTotal + $fraisLivraison;

        $addition = Addition::where('visite_id', $visiteId)->where('statut', StatutAddition::OUVERTE)->first();

        if ($addition) {
            $addition->update(['sous_total' => $sousTotal, 'total' => $total]);
        } else {
            Addition::create([
                'visite_id' => $visiteId, 'sous_total' => $sousTotal, 'remise' => 0,
                'taxe' => 0, 'total' => $total, 'statut' => StatutAddition::OUVERTE,
            ]);
        }
    }
}