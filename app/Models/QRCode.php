<?php

namespace App\Models;

use App\Enums\TypeQRCode;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QRCode extends Model
{
    use HasUuids, BelongsToRestaurant;

    protected $table = 'qr_codes';

    public $timestamps = false; // seul created_at existe (diagramme)

    protected $fillable = [
        'restaurant_id',
        'table_id',
        'code',
        'url',
        'image',
        'type',
        'date_expiration',
        'nombre_scan',
        'est_actif',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeQRCode::class,
            'est_actif' => 'boolean',
            'date_expiration' => 'datetime',
            'nombre_scan' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(TableRestaurant::class, 'table_id');
    }
}