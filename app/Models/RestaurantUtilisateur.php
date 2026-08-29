<?php

namespace App\Models;

use App\Enums\StatutAcces;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantUtilisateur extends Model
{
    use HasUuids;

    protected $table = 'restaurant_utilisateur';

    protected $fillable = [
        'restaurant_id',
        'utilisateur_id',
        'role_id',
        'statut',
        'date_invitation',
        'date_acceptation',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutAcces::class,
            'date_invitation' => 'datetime',
            'date_acceptation' => 'datetime',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function estActif(): bool
    {
        return $this->statut === StatutAcces::ACTIF;
    }
}