<?php

namespace App\Models;

use App\Enums\StatutLivraison;
use App\Enums\TypeLivreur;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pas de restaurant_id propre (diagramme) : scopée transitivement via Commande. */
class Livraison extends Model
{
    use HasUuids;

    protected $fillable = [
        'commande_id', 'adresse_id', 'nom_client', 'telephone_client', 'instructions',
        'type_livreur', 'livreur_employe_id', 'nom_livreur_externe', 'telephone_livreur_externe',
        'statut', 'zone_livraison_id', 'date_affectation',
    ];

    protected function casts(): array
    {
        return [
            'type_livreur' => TypeLivreur::class,
            'statut' => StatutLivraison::class,
            'date_affectation' => 'datetime',
        ];
    }

    public function commande(): BelongsTo { return $this->belongsTo(Commande::class); }
    public function adresse(): BelongsTo { return $this->belongsTo(AdresseLivraison::class, 'adresse_id'); }
    public function livreurEmploye(): BelongsTo { return $this->belongsTo(Employe::class, 'livreur_employe_id'); }
    public function zoneLivraison(): BelongsTo { return $this->belongsTo(ZoneLivraison::class); }
}