<?php

namespace App\Models;

use App\Enums\MethodePaiement;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoyenPaiement extends Model
{
    use HasUuids, BelongsToRestaurant;

    public $timestamps = false;

    protected $fillable = ['restaurant_id', 'methode', 'est_actif', 'identifiant_marchand'];

    protected function casts(): array
    {
        return ['methode' => MethodePaiement::class, 'est_actif' => 'boolean'];
    }

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
}