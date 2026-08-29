<?php

namespace App\Models;

use App\Enums\StatutProduit;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produit extends Model
{
    use HasUuids, BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id',
        'nom',
        'description',
        'prix',
        'est_disponible',
        'est_visible',
        'est_populaire',
        'temps_preparation',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'est_disponible' => 'boolean',
            'est_visible' => 'boolean',
            'est_populaire' => 'boolean',
            'statut' => StatutProduit::class,
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Categorie::class, 'categorie_produit')
            ->withPivot('ordre_affichage');
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(Variante::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ImageProduit::class)->orderBy('ordre_affichage');
    }
}