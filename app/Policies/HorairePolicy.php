<?php

namespace App\Policies;

use App\Models\Horaire;
use App\Models\User;
use App\Services\TenantContext;

class HorairePolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool { return $this->tenant->estDefini(); }
    public function update(User $user, Horaire $horaire): bool { return $this->tenant->aLaPermission('parametre.gerer'); }
}