<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ZoneLivraison;
use App\Services\TenantContext;

class ZoneLivraisonPolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool { return $this->tenant->estDefini(); }
    public function create(User $user): bool { return $this->tenant->aLaPermission('parametre.gerer'); }
    public function update(User $user, ZoneLivraison $zone): bool { return $this->tenant->aLaPermission('parametre.gerer'); }
    public function delete(User $user, ZoneLivraison $zone): bool { return $this->tenant->aLaPermission('parametre.gerer'); }
}