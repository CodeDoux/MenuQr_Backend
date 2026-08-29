<?php

namespace App\Models;

use App\Enums\JourSemaine;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horaire extends Model
{
    use HasUuids, BelongsToRestaurant;

    public $timestamps = false;

    protected $fillable = ['restaurant_id', 'jour_semaine', 'heure_ouverture', 'heure_fermeture', 'est_ferme'];

    protected function casts(): array
    {
        return ['jour_semaine' => JourSemaine::class, 'est_ferme' => 'boolean'];
    }

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
}