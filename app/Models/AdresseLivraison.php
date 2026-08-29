<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pas de restaurant_id : rattachée au Client (lui-même global), pas au restaurant. */
class AdresseLivraison extends Model
{
    use HasUuids;

    protected $table = 'adresses_livraison';

    protected $fillable = ['client_id', 'nom_adresse', 'adresse_complete', 'quartier', 'ville', 'region', 'indications'];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}