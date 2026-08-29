<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureAbonnement extends Model
{
    use HasUuids;

    protected $fillable = ['abonnement_id', 'numero', 'montant', 'date_emission', 'date_echeance', 'statut', 'pdf'];

    public function abonnement(): BelongsTo { return $this->belongsTo(Abonnement::class); }
}
