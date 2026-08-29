<?php

namespace App\Policies;

use App\Models\Salle;
use App\Models\User;
use App\Services\TenantContext;

class SallePolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool
    {
        return $this->tenant->estDefini();
    }

    public function view(User $user, Salle $salle): bool
    {
        return $this->tenant->estDefini();
    }

    public function create(User $user): bool
    {
        return $this->tenant->aLaPermission('table.gerer');
    }

    public function update(User $user, Salle $salle): bool
    {
        return $this->tenant->aLaPermission('table.gerer');
    }

    public function delete(User $user, Salle $salle): bool
    {
        return $this->tenant->aLaPermission('table.gerer');
    }
}