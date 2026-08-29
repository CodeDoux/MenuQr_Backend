<?php

namespace App\Models;

use App\Enums\CiblePromotion;
use App\Enums\TypeReduction;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promotion extends Model
{
    use HasUuids, BelongsToRestaurant;

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'restaurant_id', 'nom', 'code', 'type_reduction', 'valeur', 'cible',
        'date_debut', 'date_fin', 'limite_utilisation', 'nombre_utilisations', 'est_active',
    ];

    protected function casts(): array
    {
        return [
            'type_reduction' => TypeReduction::class, 'cible' => CiblePromotion::class,
            'valeur' => 'decimal:2', 'date_debut' => 'datetime', 'date_fin' => 'datetime', 'est_active' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }

    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(Produit::class, 'promotion_produit');
    }
}