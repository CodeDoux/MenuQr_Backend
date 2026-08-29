<?php

namespace App\Models;

use App\Enums\StatutZone;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneLivraison extends Model
{
    use HasUuids, BelongsToRestaurant;

    protected $table = 'zones_livraison';

    protected $fillable = ['restaurant_id', 'nom', 'description', 'frais', 'temps_estime', 'distance_max', 'statut'];

    protected function casts(): array
    {
        return ['statut' => StatutZone::class, 'frais' => 'decimal:2', 'distance_max' => 'decimal:2'];
    }

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
}