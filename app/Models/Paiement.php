<?php

namespace App\Models;

use App\Enums\StatutPaiement;
use App\Enums\TypePaiement;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    use HasUuids;

    protected $fillable = [
        'type', 'commande_id', 'addition_id', 'facture_abonnement_id',
        'montant', 'devise', 'methode', 'statut', 'reference', 'date_paiement',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypePaiement::class, 'statut' => StatutPaiement::class,
            'montant' => 'decimal:2', 'date_paiement' => 'datetime',
        ];
    }

    public function commande(): BelongsTo { return $this->belongsTo(Commande::class); }
    public function addition(): BelongsTo { return $this->belongsTo(Addition::class); }
    public function factureAbonnement(): BelongsTo { return $this->belongsTo(FactureAbonnement::class); }
}