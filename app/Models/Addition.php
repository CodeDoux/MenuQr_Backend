<?php

namespace App\Models;

use App\Enums\StatutAddition;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Addition extends Model
{
    use HasUuids;

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = ['visite_id', 'sous_total', 'remise', 'taxe', 'total', 'statut'];

    protected function casts(): array
    {
        return [
            'statut' => StatutAddition::class,
            'sous_total' => 'decimal:2', 'remise' => 'decimal:2',
            'taxe' => 'decimal:2', 'total' => 'decimal:2',
        ];
    }

    public function visite(): BelongsTo { return $this->belongsTo(Visite::class); }
    public function paiements(): HasMany { return $this->hasMany(Paiement::class); }
}