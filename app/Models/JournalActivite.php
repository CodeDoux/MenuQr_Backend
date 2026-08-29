<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalActivite extends Model
{
    use HasUuids, BelongsToRestaurant;

    public $timestamps = false;

    protected $table = 'journal_activite';

    protected $fillable = ['restaurant_id', 'utilisateur_id', 'action', 'table_cible', 'id_cible', 'ancienne_valeur', 'nouvelle_valeur', 'date'];

    protected function casts(): array
    {
        return ['ancienne_valeur' => 'array', 'nouvelle_valeur' => 'array', 'date' => 'datetime'];
    }

    public function utilisateur(): BelongsTo { return $this->belongsTo(User::class, 'utilisateur_id'); }
}