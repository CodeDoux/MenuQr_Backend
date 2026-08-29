<?php

namespace App\Models\Concerns;

use App\Models\Scopes\RestaurantScope;
use App\Services\TenantContext;

/**
 * À utiliser sur tout modèle portant directement une colonne restaurant_id
 * (Menu, Produit, Commande, Salle, Employe, etc.). Les modèles scopés
 * transitivement (Categorie via Menu, Variante via Produit...) n'en ont pas
 * besoin — ils héritent de l'isolation via leur relation parente.
 */
trait BelongsToRestaurant
{
    protected static function bootBelongsToRestaurant(): void
    {
        static::addGlobalScope(new RestaurantScope);

        static::creating(function ($model) {
            if (empty($model->restaurant_id)) {
                $tenant = app(TenantContext::class);
                $model->restaurant_id = $tenant->restaurantId;
            }
        });
    }
}