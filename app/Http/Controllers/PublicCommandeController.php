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
use App\Services\PaydunyaService;
use App\Services\AdditionService;

/**
 * ⚠️ Routes publiques. Le restaurant/table sont TOUJOURS résolus via le
 * "code" du QR (jamais via un id envoyé par le client). Les prix des
 * lignes sont TOUJOURS recalculés depuis Produit/Variante en base — jamais
 * un prix envoyé par le client (protection contre la manipulation de prix).
 */
class PublicCommandeController extends Controller
{

    public function __construct(private AdditionService $additionService) {}

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
                ? $this->additionService->calculerRemisePromotions($qrCode->restaurant_id, $sousTotal, $lignes)
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
                $this->additionService->recalculerAddition($visiteId);
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

    /** Paiement en ligne côté client (addition de visite OU commande directe),
 *  via PayDunya — le client choisit Wave/Orange Money/Carte SUR la page
 *  PayDunya elle-même, pas chez nous. */
public function payer(string $commandeId, PaydunyaService $paydunya)
{
    $commande = Commande::withoutGlobalScope(RestaurantScope::class)->findOrFail($commandeId);

    $montant = null;
    $additionId = null;

    if ($commande->visite_id) {
        $addition = Addition::where('visite_id', $commande->visite_id)
            ->where('statut', StatutAddition::OUVERTE)->firstOrFail();
        $montant = (float) $addition->total;
        $additionId = $addition->id;
    } else {
        $montant = (float) $commande->total;
    }

    $paiement = Paiement::create([
        'type' => TypePaiement::COMMANDE,
        'addition_id' => $additionId,
        'commande_id' => $additionId ? null : $commande->id,
        'montant' => $montant,
        'devise' => 'FCFA',
        'methode' => 'AUTRE', // ⚠️ inconnu tant que le client n'a pas choisi sur PayDunya
        'statut' => StatutPaiement::EN_ATTENTE,
    ]);

    try {
        $resultat = $paydunya->creerFacture(
            $montant,
            "Commande MenuQr #".strtoupper(substr($commande->id, -4)),
            config('app.frontend_url')."/m/{$commande->restaurant_id}/suivi/{$commande->id}?paiement=retour",
            config('app.url').'/api/public/paydunya/webhook'
        );
    } catch (\RuntimeException $e) {
        $paiement->update(['statut' => StatutPaiement::ECHOUE]);
        return response()->json(['message' => $e->getMessage()], 422);
    }

    $paiement->update(['reference' => $resultat['token']]);

    return response()->json(['url_paiement' => $resultat['url']]);
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