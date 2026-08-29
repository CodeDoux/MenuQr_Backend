<?php

namespace App\Models;

use App\Enums\StatutAbonnement;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Abonnement extends Model
{
    use HasUuids;

    protected $fillable = [
        'restaurant_id',
        'offre_id',
        'date_debut',
        'date_fin',
        'statut',
        'renouvellement_automatique',
        'date_prochain_paiement',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutAbonnement::class,
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'date_prochain_paiement' => 'datetime',
            'renouvellement_automatique' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function offre(): BelongsTo
    {
        return $this->belongsTo(Offre::class);
    }
}