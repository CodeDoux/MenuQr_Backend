<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Fonctionnalite extends Model
{
    use HasUuids;

    protected $fillable = ['nom', 'code', 'description'];

    public function offres(): BelongsToMany
    {
        return $this->belongsToMany(Offre::class, 'offre_fonctionnalite');
    }
}