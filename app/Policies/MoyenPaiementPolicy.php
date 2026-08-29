<?php

namespace App\Policies;

use App\Models\MoyenPaiement;
use App\Models\User;
use App\Services\TenantContext;

class MoyenPaiementPolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool { return $this->tenant->estDefini(); }
    public function update(User $user, MoyenPaiement $moyen): bool { return $this->tenant->aLaPermission('parametre.gerer'); }
}