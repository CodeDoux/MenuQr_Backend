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
use Illuminate\Http\Request;

/**
 * ⚠️ Routes publiques : le restaurant est résolu via le "code" du QR scanné
 * (jamais via un restaurant_id envoyé par le client — non fiable). Le
 * RestaurantScope de chaque modèle est contourné explicitement puisqu'aucun
 * TenantContext n'existe dans ce contexte non authentifié.
 */
class PublicMenuController extends Controller
{
    public function show(Request $request)
    {
        $code = $request->query('code');
        $qrCode = QRCode::withoutGlobalScope(RestaurantScope::class)
            ->where('code', $code)->where('est_actif', true)->first();

        if (!$qrCode) {
            return response()->json(['message' => 'QR code invalide ou expiré.'], 404);
        }

        $qrCode->increment('nombre_scan');

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

        $table = $qrCode->table_id
            ? TableRestaurant::find($qrCode->table_id)
            : null;

        $zones = \App\Models\ZoneLivraison::withoutGlobalScope(RestaurantScope::class)
            ->where('restaurant_id', $qrCode->restaurant_id)
            ->where('statut', 'ACTIVE')
            ->get();

        return response()->json([
            'restaurant_id' => $qrCode->restaurant_id,
            'type_qr' => $qrCode->type,
            'table_id' => $table?->id,
            'table_numero' => $table?->numero,
            'menus' => MenuResource::collection($menus),
            'produits' => ProduitResource::collection($produits),
            'zones_livraison' => \App\Http\Resources\ZoneLivraisonResource::collection($zones),
        ]);
    }
}