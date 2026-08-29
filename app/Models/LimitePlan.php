<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LimitePlan extends Model
{
    use HasUuids;

    protected $table = 'limites_plan';

    public $timestamps = false;

    protected $fillable = ['offre_id', 'nom', 'valeur', 'unite'];

    public function offre(): BelongsTo { return $this->belongsTo(Offre::class); }
}