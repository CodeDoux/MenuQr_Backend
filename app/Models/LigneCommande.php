<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneCommande extends Model
{
    use HasUuids;

    protected $table = 'lignes_commande';

    public $timestamps = false;

    protected $fillable = ['commande_id', 'produit_id', 'quantite', 'prix_unitaire', 'sous_total', 'notes'];

    protected function casts(): array
    {
        return ['prix_unitaire' => 'decimal:2', 'sous_total' => 'decimal:2'];
    }

    public function commande(): BelongsTo { return $this->belongsTo(Commande::class); }
    public function produit(): BelongsTo { return $this->belongsTo(Produit::class); }
}
>