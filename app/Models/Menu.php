<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasUuids, BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id',
        'nom',
        'description',
        'image',
        'ordre_affichage',
        'est_actif',
        'date_debut',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'est_actif' => 'boolean',
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Categorie::class);
    }
}