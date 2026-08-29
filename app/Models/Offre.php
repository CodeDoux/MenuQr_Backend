<?php

namespace App\Models;

use App\Enums\StatutPlan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offre extends Model
{
    use HasUuids;

    protected $fillable = [
        'nom',
        'description',
        'prix_mensuel',
        'prix_annuel',
        'devise',
        'duree_essai',
        'statut',
        'ordre_affichage',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutPlan::class,
            'prix_mensuel' => 'decimal:2',
            'prix_annuel' => 'decimal:2',
        ];
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }
}