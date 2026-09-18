<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalAdmin extends Model
{
    use HasUuids;

    protected $table = 'journal_admin';
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'action', 'table_cible', 'id_cible', 'ancienne_valeur', 'nouvelle_valeur', 'date',
    ];

    protected function casts(): array
    {
        return ['date' => 'datetime'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUtilisateur::class, 'admin_id');
    }
}