<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'utilisateur_id', 'client_id', 'titre', 'message', 'type', 'lien', 'est_lu', 'date_envoie',
    ];

    protected $casts = [
        'est_lu' => 'boolean',
        'date_envoie' => 'datetime',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}