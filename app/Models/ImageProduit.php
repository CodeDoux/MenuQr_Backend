<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageProduit extends Model
{
    use HasUuids;

    public $timestamps = false; // seul created_at existe (diagramme)
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $table = 'images_produit';

    protected $fillable = [
        'produit_id',
        'url',
        'ordre_affichage',
        'est_principale',
    ];

    protected function casts(): array
    {
        return [
            'est_principale' => 'boolean',
        ];
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}