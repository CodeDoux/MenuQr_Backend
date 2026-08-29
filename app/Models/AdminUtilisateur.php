<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Volontairement AUCUN lien avec Restaurant/RestaurantUtilisateur — un
 * admin MenuQR voit toute la plateforme, jamais scopé à un tenant.
 */
class AdminUtilisateur extends Authenticatable
{
    use HasApiTokens, HasUuids;

    protected $fillable = ['nom_complet', 'email', 'password', 'actif'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'actif' => 'boolean'];
    }
}