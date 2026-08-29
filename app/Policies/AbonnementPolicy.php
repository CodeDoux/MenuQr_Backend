<?php

namespace App\Policies;

use App\Models\User;
use App\Services\TenantContext;

class AbonnementPolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function voir(User $user): bool { return $this->tenant->estDefini(); }
    public function gerer(User $user): bool { return $this->tenant->aLaPermission('abonnement.gerer'); }
}