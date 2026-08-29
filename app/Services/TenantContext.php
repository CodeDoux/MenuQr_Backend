<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;

/**
 * Contexte tenant de la requête en cours — renseigné par EnsureRestaurantAccess
 * à partir du token Sanctum (jamais depuis un id envoyé par le frontend).
 * Enregistré comme singleton dans le conteneur (voir AppServiceProvider).
 */
class TenantContext
{
    public ?string $restaurantId = null;
    public ?Restaurant $restaurant = null;
    public ?RestaurantUtilisateur $acces = null;

    public function definir(Restaurant $restaurant, RestaurantUtilisateur $acces): void
    {
        $this->restaurantId = $restaurant->id;
        $this->restaurant = $restaurant;
        $this->acces = $acces;
    }

    public function estDefini(): bool
    {
        return $this->restaurantId !== null;
    }

    public function aLaPermission(string $code): bool
    {
        return $this->acces?->role?->aLaPermission($code) ?? false;
    }
}