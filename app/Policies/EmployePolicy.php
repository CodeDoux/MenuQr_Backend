<?php

namespace App\Policies;

use App\Models\Employe;
use App\Models\User;
use App\Services\TenantContext;

class EmployePolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool
    {
        return $this->tenant->estDefini();
    }

    public function create(User $user): bool
    {
        return $this->tenant->aLaPermission('employe.gerer');
    }

    public function update(User $user, Employe $employe): bool
    {
        return $this->tenant->aLaPermission('employe.gerer');
    }

    public function delete(User $user, Employe $employe): bool
    {
        return $this->tenant->aLaPermission('employe.gerer');
    }
}