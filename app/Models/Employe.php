<?php

namespace App\Models;

use App\Enums\StatutEmploye;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employe extends Model
{
    use HasUuids, BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id',
        'utilisateur_id',
        'poste_id',
        'restaurant_utilisateur_id',
        'matricule',
        'date_embauche',
        'date_fin',
        'statut',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutEmploye::class,
            'date_embauche' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class);
    }

    public function accesPlateforme(): BelongsTo
    {
        return $this->belongsTo(RestaurantUtilisateur::class, 'restaurant_utilisateur_id');
    }
}