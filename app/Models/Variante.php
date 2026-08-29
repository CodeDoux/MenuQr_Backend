<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variante extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'produit_id',
        'nom',
        'prix',
        'est_disponible',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'est_disponible' => 'boolean',
        ];
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}