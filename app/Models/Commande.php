<?php

namespace App\Models;

use App\Enums\ModeCommande;
use App\Enums\StatutCommande;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Commande extends Model
{
    use HasUuids, BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id', 'client_id', 'visite_id', 'table_id', 'mode', 'statut',
        'sous_total', 'frais_livraison', 'remise', 'total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'mode' => ModeCommande::class,
            'statut' => StatutCommande::class,
            'sous_total' => 'decimal:2', 'frais_livraison' => 'decimal:2',
            'remise' => 'decimal:2', 'total' => 'decimal:2',
        ];
    }

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function visite(): BelongsTo { return $this->belongsTo(Visite::class); }
    public function table(): BelongsTo { return $this->belongsTo(TableRestaurant::class, 'table_id'); }
    public function lignes(): HasMany { return $this->hasMany(LigneCommande::class); }
    public function historique(): HasMany { return $this->hasMany(HistoriqueCommande::class); }
    public function paiements(): HasMany { return $this->hasMany(Paiement::class); }
    public function livraison(): HasOne { return $this->hasOne(Livraison::class); }
}