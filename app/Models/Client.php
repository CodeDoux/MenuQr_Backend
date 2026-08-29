<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pas de restaurant_id : un client peut visiter plusieurs restaurants (anonyme, sans compte). */
class Client extends Model
{
    use HasUuids;

    protected $fillable = ['nom', 'prenom', 'telephone', 'email'];

    public function visites(): HasMany
    {
        return $this->hasMany(Visite::class);
    }
}
>