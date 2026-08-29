<?php

namespace App\Models;

use App\Enums\StatutVisite;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visite extends Model
{
    use HasUuids, BelongsToRestaurant;

    public $timestamps = false;

    protected $fillable = ['restaurant_id', 'client_id', 'table_id', 'date_debut', 'date_fin', 'statut'];

    protected function casts(): array
    {
        return ['statut' => StatutVisite::class, 'date_debut' => 'datetime', 'date_fin' => 'datetime'];
    }

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function table(): BelongsTo { return $this->belongsTo(TableRestaurant::class, 'table_id'); }
    public function commandes(): HasMany { return $this->hasMany(Commande::class); }
    public function addition(): HasOne { return $this->hasOne(Addition::class); }
}