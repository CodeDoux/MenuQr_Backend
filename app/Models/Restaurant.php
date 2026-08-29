<?php

namespace App\Models;

use App\Enums\StatutRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasUuids;

    protected $fillable = [
        'nom',
        'logo',
        'adresse',
        'telephone',
        'email',
        'description',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutRestaurant::class,
        ];
    }

    public function abonnements(): HasMany
{
    return $this->hasMany(Abonnement::class);
}

    public function restaurantUtilisateurs(): HasMany
    {
        return $this->hasMany(RestaurantUtilisateur::class);
    }

    /** Utilisateurs ayant un accès (tous statuts confondus) à ce restaurant. */
    public function utilisateurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'restaurant_utilisateur', 'restaurant_id', 'utilisateur_id')
            ->withPivot(['role_id', 'statut', 'date_invitation', 'date_acceptation'])
            ->withTimestamps();
    }

    // Les autres relations (Menu, Employe, Abonnement, etc.) seront ajoutées
    // au fur et à mesure de la construction des modules correspondants.
}