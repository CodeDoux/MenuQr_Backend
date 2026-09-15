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
use App\Models\Notification;
use App\Models\Paiement;
use App\Models\Produit;
use App\Models\QRCode;
use App\Models\RestaurantUtilisateur;
use App\Models\Scopes\RestaurantScope;
use App\Models\TableRestaurant;
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

            // Emporter/Livraison : remise calculée ici, sur cette commande seule.
            // Sur place : remise calculée plus tard, au niveau de l'ADDITION
            // entière (voir recalculerAddition()) — sinon une promo "montant
            // fixe" serait déduite plusieurs fois si la visite a plusieurs commandes.
            $remiseTotale = $data['mode'] !== 'SUR_PLACE'
                ? $this->calculerRemisePromotions($qrCode->restaurant_id, $sousTotal, $lignes)
                : 0;
            $promotionUtilisee = $remiseTotale > 0;

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

                    // ⚠️ Rien ne marquait jamais la table comme occupée —
                    // corrigé ici, au moment précis où une visite démarre.
                    \App\Models\TableRestaurant::withoutGlobalScope(RestaurantScope::class)
                        ->where('id', $qrCode->table_id)
                        ->update(['statut' => 'OCCUPEE']);
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
                'remise' => $remiseTotale,
                'total' => $sousTotal + $fraisLivraison - $remiseTotale,
                'notes' => $data['notes'] ?? null,
                'nom_client' => $data['nom_client'] ?? null,
                'telephone_client' => $data['telephone_client'] ?? null,
                'heure_retrait_souhaitee' => isset($data['heure_retrait_souhaitee'])
                    ? \Illuminate\Support\Carbon::parse($data['heure_retrait_souhaitee'])
                    : null,
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
                // Sur place : la remise (s'il y en a une) est calculée ICI,
                // pour l'addition entière — voir recalculerAddition().
                $this->recalculerAddition($visiteId);
            } elseif ($promotionUtilisee) {
                // Emporter/Livraison : incrémente immédiatement, une fois par commande.
                \App\Models\Promotion::withoutGlobalScope(RestaurantScope::class)
                    ->where('restaurant_id', $qrCode->restaurant_id)
                    ->where('est_active', true)
                    ->increment('nombre_utilisations');
            }

            $this->notifierNouvelleCommande($commande, $qrCode->restaurant_id, $tableId);

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

    /** Récupère les autres commandes de la MÊME addition (donc jamais celles
     *  déjà payées via une addition précédente sur la même visite), ainsi
     *  que le total RÉEL de l'addition (remise incluse — vit sur l'addition,
     *  pas sur chaque commande individuelle, voir recalculerAddition()). */
    public function commandesDeLaVisite(string $commandeId)
    {
        $commande = Commande::withoutGlobalScope(RestaurantScope::class)->findOrFail($commandeId);
        if (!$commande->visite_id) {
            return response()->json([
                'data' => CommandeResource::collection(collect([$commande]))->resolve(),
                'addition_total' => null,
                'addition_remise' => null,
            ]);
        }

        $commandes = Commande::withoutGlobalScope(RestaurantScope::class)
            ->where('visite_id', $commande->visite_id)
            ->where('addition_id', $commande->addition_id)
            ->with(['lignes.produit' => fn ($q) => $q->withoutGlobalScope(RestaurantScope::class)])
            ->get();

        $addition = $commande->addition_id ? Addition::find($commande->addition_id) : null;

        return response()->json([
            'data' => CommandeResource::collection($commandes)->resolve(),
            'addition_sous_total' => $addition?->sous_total,
            'addition_remise' => $addition?->remise,
            'addition_total' => $addition?->total,
        ]);
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

    /**
     * ⚠️ CORRIGÉ — l'ancienne version resommait TOUTES les commandes de la
     * visite à chaque appel, y compris celles déjà couvertes par une
     * addition précédente déjà payée (double-facturation). Chaque commande
     * est maintenant explicitement rattachée à UNE addition précise
     * (commandes.addition_id) dès qu'elle y est intégrée, et n'est plus
     * jamais reprise dans le calcul d'une addition suivante.
     *
     * La remise des promotions est calculée ICI, pour l'ADDITION ENTIÈRE
     * (toutes les commandes qui lui sont rattachées), jamais commande par
     * commande — sinon une promo "montant fixe" serait déduite plusieurs
     * fois si la visite contient plusieurs commandes.
     */
    private function recalculerAddition(string $visiteId): void
    {
        $addition = Addition::where('visite_id', $visiteId)->where('statut', StatutAddition::OUVERTE)->first();
        $nouvelleAddition = !$addition;

        if (!$addition) {
            $addition = Addition::create([
                'visite_id' => $visiteId, 'sous_total' => 0, 'remise' => 0,
                'taxe' => 0, 'total' => 0, 'statut' => StatutAddition::OUVERTE,
            ]);
        }

        Commande::withoutGlobalScope(RestaurantScope::class)
            ->where('visite_id', $visiteId)
            ->where('statut', '!=', StatutCommande::ANNULEE)
            ->whereNull('addition_id')
            ->update(['addition_id' => $addition->id]);

        $commandes = Commande::withoutGlobalScope(RestaurantScope::class)
            ->where('addition_id', $addition->id)
            ->with('lignes')->get();

        $sousTotal = $commandes->flatMap->lignes->sum('sous_total');
        $fraisLivraison = $commandes->sum('frais_livraison');

        $lignesPourPromo = $commandes->flatMap->lignes->map(fn ($l) => [
            'produit_id' => $l->produit_id, 'quantite' => $l->quantite, 'sous_total' => (float) $l->sous_total,
        ])->all();

        $restaurantId = $commandes->first()?->restaurant_id;
        $remise = $restaurantId ? $this->calculerRemisePromotions($restaurantId, $sousTotal, $lignesPourPromo) : 0;
        $total = $sousTotal + $fraisLivraison - $remise;

        $addition->update(['sous_total' => $sousTotal, 'remise' => $remise, 'total' => $total]);

        // Une seule fois par visite (à la création de l'addition), pas à chaque commande.
        if ($nouvelleAddition && $remise > 0 && $restaurantId) {
            \App\Models\Promotion::withoutGlobalScope(RestaurantScope::class)
                ->where('restaurant_id', $restaurantId)
                ->where('est_active', true)
                ->increment('nombre_utilisations');
        }
    }

    /**
     * Calcule la remise totale des promotions éligibles pour un ensemble de
     * lignes données. Réutilisée à la fois pour une commande standalone
     * (Emporter/Livraison) et pour une addition entière (Sur place).
     *
     * @param array $lignes Tableau de ['produit_id' => ..., 'quantite' => ..., 'sous_total' => ...]
     */
    private function calculerRemisePromotions(string $restaurantId, float $sousTotal, array $lignes): float
    {
        $promotionsEligibles = \App\Models\Promotion::withoutGlobalScope(RestaurantScope::class)
            ->where('restaurant_id', $restaurantId)
            ->where('est_active', true)
            ->where('date_debut', '<=', now())
            ->where(function ($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('limite_utilisation')->orWhereColumn('nombre_utilisations', '<', 'limite_utilisation');
            })
            ->with(['produits' => fn ($q) => $q->withoutGlobalScope(RestaurantScope::class)])
            ->get();

        $remiseTotale = 0;

        foreach ($promotionsEligibles as $promotion) {
            $cible = $promotion->cible instanceof \BackedEnum ? $promotion->cible->value : $promotion->cible;
            $typeReduction = $promotion->type_reduction instanceof \BackedEnum ? $promotion->type_reduction->value : $promotion->type_reduction;

            if ($cible === 'PRODUIT') {
                $produitIdsPromo = $promotion->produits->pluck('id')->all();
                foreach ($lignes as $ligne) {
                    if (in_array($ligne['produit_id'], $produitIdsPromo, true)) {
                        $remiseTotale += $typeReduction === 'POURCENTAGE'
                            ? $ligne['sous_total'] * ((float) $promotion->valeur / 100)
                            : (float) $promotion->valeur * $ligne['quantite'];
                    }
                }
            } else { // COMMANDE_ENTIERE
                $remiseTotale += $typeReduction === 'POURCENTAGE'
                    ? $sousTotal * ((float) $promotion->valeur / 100)
                    : (float) $promotion->valeur;
            }
        }

        return min($remiseTotale, $sousTotal);
    }

    /**
     * Notifie chaque membre du staff ayant la permission de faire avancer
     * une commande (Propriétaire, Gérant, Serveur, Cuisinier) qu'une
     * nouvelle commande vient d'arriver.
     */
    private function notifierNouvelleCommande(Commande $commande, string $restaurantId, ?string $tableId): void
    {
        $staffANotifier = RestaurantUtilisateur::withoutGlobalScope(RestaurantScope::class)
            ->where('restaurant_id', $restaurantId)
            ->where('statut', 'ACTIF')
            ->whereHas('role.permissions', fn ($q) => $q->where('code', 'commande.gerer_statut'))
            ->get();

        $numero = '#'.strtoupper(substr($commande->id, -4));
        $suffixeTable = '';
        if ($tableId) {
            $table = TableRestaurant::withoutGlobalScope(RestaurantScope::class)->find($tableId);
            if ($table) {
                $suffixeTable = " — Table {$table->numero}";
            }
        }

        foreach ($staffANotifier as $acces) {
            Notification::create([
                'utilisateur_id' => $acces->utilisateur_id,
                'titre' => 'Nouvelle commande',
                'message' => "Commande {$numero} reçue{$suffixeTable}",
                'type' => 'NOUVELLE_COMMANDE',
                'lien' => '/commandes',
                'est_lu' => false,
                'date_envoie' => now(),
            ]);
        }
    }
}