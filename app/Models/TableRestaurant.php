<?php

namespace App\Models;

use App\Enums\StatutTable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pas de restaurant_id direct (Correction #4 validee) : toujours accedee
 * via sa Salle parente (deja scopee), jamais TableRestaurant::findOrFail()
 * directement.
 */
class TableRestaurant extends Model
{
    use HasUuids;

    protected $table = 'tables_restaurant';

    protected $fillable = [
        'salle_id',
        'numero',
        'capacite',
        'statut',
        'zone',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutTable::class,
            'capacite' => 'integer',
        ];
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QRCode::class, 'table_id');
    }
}