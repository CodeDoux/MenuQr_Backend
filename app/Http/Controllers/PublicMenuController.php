<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuResource;
use App\Http\Resources\ProduitResource;
use App\Models\Menu;
use App\Models\Produit;
use App\Models\QRCode;
use App\Models\Scopes\RestaurantScope;
use App\Models\TableRestaurant;
use App\Services\MenuPublicCacheService;
use Illuminate\Http\Request;

/**
 * ⚠️ Routes publiques : le restaurant est résolu via le "code" du QR scanné
 * (jamais via un restaurant_id envoyé par le client — non fiable). Le
 * RestaurantScope de chaque modèle est contourné explicitement puisqu'aucun
 * TenantContext n'existe dans ce contexte non authentifié.
 *
 * ⚠️ Mis en cache (voir MenuPublicCacheService) — c'est l'endpoint le plus
 * sollicité de toute l'API (chaque scan de QR, sans authentification).
 * Seule la partie propre au RESTAURANT (menus/produits/horaires/paiement)
 * est cachée ; la résolution du QR et de la table reste toujours fraîche
 * (nombre_scan s'incrémente à chaque appel, jamais caché).
 */
class PublicMenuController extends Controller
{
    public function __construct(private readonly MenuPublicCacheService $cache) {}

    public function show(Request $request)
    {
        $code = $request->query('code');
        $qrCode = QRCode::withoutGlobalScope(RestaurantScope::class)
            ->where('code', $code)->where('est_actif', true)->first();

        if (!$qrCode) {
            return response()->json(['message' => 'QR code invalide ou expiré.'], 404);
        }

        $qrCode->increment('nombre_scan');

        $table = $qrCode->table_id
            ? \App\Models\TableRestaurant::withoutGlobalScope(RestaurantScope::class)
                ->with(['salle' => fn ($q) => $q->withoutGlobalScope(RestaurantScope::class)])
                ->find($qrCode->table_id)
            : null;

        // --- Tout ce qui est propre au RESTAURANT (pas au QR précis) est mis
        // en cache, invalidé automatiquement à chaque modification (voir
        // MenuPublicCacheService::invalider() appelé dans les contrôleurs
        // staff : Menu, Catégorie, Produit, Horaire, MoyenPaiement, Restaurant).
        $donneesRestaurant = $this->cache->souvenir($qrCode->restaurant_id, function () use ($qrCode) {
            $menus = Menu::withoutGlobalScope(RestaurantScope::class)
                ->where('restaurant_id', $qrCode->restaurant_id)
                ->where('est_actif', true)
                ->with(['categories' => fn ($q) => $q->where('est_active', true)->orderBy('ordre_affichage')])
                ->orderBy('ordre_affichage')
                ->get();

            $produits = Produit::withoutGlobalScope(RestaurantScope::class)
                ->where('restaurant_id', $qrCode->restaurant_id)
                ->where('statut', 'ACTIF')->where('est_visible', true)
                ->with(['categories', 'variantes', 'images'])
                ->get();

            $restaurant = \App\Models\Restaurant::withoutGlobalScope(RestaurantScope::class)->find($qrCode->restaurant_id);

            $zones = \App\Models\ZoneLivraison::withoutGlobalScope(RestaurantScope::class)
                ->where('restaurant_id', $qrCode->restaurant_id)
                ->where('statut', 'ACTIVE')
                ->get();

            $horaires = \App\Models\Horaire::withoutGlobalScope(RestaurantScope::class)
                ->where('restaurant_id', $qrCode->restaurant_id)
                ->orderByRaw("array_position(ARRAY['LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE'], jour_semaine)")
                ->get()
                ->map(fn ($h) => [
                    'jour' => $h->jour_semaine,
                    'ouverture' => $h->heure_ouverture,
                    'fermeture' => $h->heure_fermeture,
                    'ferme' => $h->est_ferme,
                ]);

            $moyensPaiement = \App\Models\MoyenPaiement::withoutGlobalScope(RestaurantScope::class)
                ->where('restaurant_id', $qrCode->restaurant_id)
                ->where('est_actif', true)
                ->get()
                ->pluck('methode');

            return [
                'restaurant_nom' => $restaurant?->nom,
                'restaurant_adresse' => $restaurant?->adresse,
                'restaurant_telephone' => $restaurant?->telephone,
                'restaurant_description' => $restaurant?->description,
                'horaires' => $horaires,
                'moyens_paiement' => $moyensPaiement,
                'menus' => MenuResource::collection($menus)->resolve(),
                'produits' => ProduitResource::collection($produits)->resolve(),
                'zones_livraison' => \App\Http\Resources\ZoneLivraisonResource::collection($zones)->resolve(),
            ];
        });

        return response()->json(array_merge($donneesRestaurant, [
            'restaurant_id' => $qrCode->restaurant_id,
            'type_qr' => $qrCode->type,
            'table_id' => $table?->id,
            'table_numero' => $table?->numero,
            'salle_nom' => $table?->salle?->description,
        ]));
    }
}