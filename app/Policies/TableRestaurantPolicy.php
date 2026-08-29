<?php

namespace App\Policies;

use App\Models\TableRestaurant;
use App\Models\User;
use App\Services\TenantContext;

class TableRestaurantPolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool
    {
        return $this->tenant->estDefini();
    }

    public function view(User $user, TableRestaurant $table): bool
    {
        return $this->tenant->estDefini();
    }

    public function create(User $user): bool
    {
        return $this->tenant->aLaPermission('table.gerer');
    }

    public function update(User $user, TableRestaurant $table): bool
    {
        return $this->tenant->aLaPermission('table.gerer');
    }

    public function delete(User $user, TableRestaurant $table): bool
    {
        return $this->tenant->aLaPermission('table.gerer');
    }
}