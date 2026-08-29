<?php

namespace App\Models;

use App\Enums\StatutFactureAbonnement;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureAbonnement extends Model
{
    use HasUuids;

    protected $table = 'factures_abonnement';

    protected $fillable = ['abonnement_id', 'numero', 'montant', 'date_emission', 'date_echeance', 'statut', 'pdf'];

    protected function casts(): array
    {
        return [
            'statut' => StatutFactureAbonnement::class, 'montant' => 'decimal:2',
            'date_emission' => 'datetime', 'date_echeance' => 'datetime',
        ];
    }

    public function abonnement(): BelongsTo { return $this->belongsTo(Abonnement::class); }
}