<?php

namespace App\Models;

use App\Enums\StatutSalle;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salle extends Model
{
    use HasUuids, BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id',
        'description',
        'ordre',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutSalle::class,
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(TableRestaurant::class);
    }
}