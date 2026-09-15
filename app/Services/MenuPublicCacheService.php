<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache du menu public — le point le plus sollicité de l'API (chaque scan
 * de QR code par un client, sans authentification). Clé par restaurant
 * (pas par QR code précis) puisque menus/produits/horaires/moyens de
 * paiement sont partagés par toutes les tables d'un même restaurant.
 */
class MenuPublicCacheService
{
    private const DUREE_MINUTES = 10;

    public function cle(string $restaurantId): string
    {
        return "menu-public:{$restaurantId}";
    }

    public function souvenir(string $restaurantId, \Closure $callback): mixed
    {
        return Cache::remember($this->cle($restaurantId), now()->addMinutes(self::DUREE_MINUTES), $callback);
    }

    public function invalider(string $restaurantId): void
    {
        Cache::forget($this->cle($restaurantId));
    }
}