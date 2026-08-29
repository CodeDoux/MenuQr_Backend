<?php

namespace App\Models;

use App\Enums\StatutUtilisateur;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Représente Utilisateur du diagramme.
 *
 * ⚠️ N'utilise volontairement PAS le trait Illuminate\Notifications\Notifiable :
 * notre modèle métier définit déjà sa propre classe Notification (domaine
 * validé), on n'introduit pas le système de notifications natif de Laravel
 * par-dessus pour éviter toute confusion/duplication.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasUuids, SoftDeletes;

    protected $fillable = [
        'nom_complet',
        'email',
        'telephone',
        'password',
        'photo',
        'statut',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'statut' => StatutUtilisateur::class,
            'email_verifie' => 'boolean',
            'telephone_verifie' => 'boolean',
            'dernier_connexion' => 'datetime',
        ];
    }

    public function restaurantUtilisateurs(): HasMany
    {
        return $this->hasMany(RestaurantUtilisateur::class, 'utilisateur_id');
    }

    /** Restaurants auxquels cet utilisateur a un accès (tous statuts confondus). */
    public function restaurants(): HasManyThrough
    {
        return $this->hasManyThrough(
            Restaurant::class,
            RestaurantUtilisateur::class,
            'utilisateur_id',
            'id',
            'id',
            'restaurant_id'
        );
    }
}