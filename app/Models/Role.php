<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasUuids;

    protected $fillable = [
        'nom',
        'code',
        'description',
        'niveau',
        'est_systeme',
    ];

    protected function casts(): array
    {
        return [
            'niveau' => 'integer',
            'est_systeme' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function restaurantUtilisateurs(): HasMany
    {
        return $this->hasMany(RestaurantUtilisateur::class);
    }

    public function aLaPermission(string $code): bool
    {
        return $this->permissions()->where('code', $code)->exists();
    }
}