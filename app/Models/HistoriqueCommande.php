<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueCommande extends Model
{
    use HasUuids;

    protected $table = 'historique_commande';

    public $timestamps = false;

    protected $fillable = ['commande_id', 'ancien_statut', 'nouveau_statut', 'utilisateur_id', 'date', 'commentaire'];

    protected function casts(): array
    {
        return ['date' => 'datetime'];
    }

    public function commande(): BelongsTo { return $this->belongsTo(Commande::class); }
    public function utilisateur(): BelongsTo { return $this->belongsTo(User::class, 'utilisateur_id'); }
}