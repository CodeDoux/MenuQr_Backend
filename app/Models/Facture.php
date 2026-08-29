<?php

namespace App\Models;

use App\Enums\StatutFacture;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facture extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['numero', 'commande_id', 'addition_id', 'montant_ht', 'taxe', 'montant_ttc', 'date_emission', 'statut'];

    protected function casts(): array
    {
        return [
            'statut' => StatutFacture::class, 'date_emission' => 'datetime',
            'montant_ht' => 'decimal:2', 'taxe' => 'decimal:2', 'montant_ttc' => 'decimal:2',
        ];
    }

    public function commande(): BelongsTo { return $this->belongsTo(Commande::class); }
    public function addition(): BelongsTo { return $this->belongsTo(Addition::class); }
}