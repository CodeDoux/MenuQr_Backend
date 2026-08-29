<?php

namespace App\Policies;

use App\Models\Categorie;
use App\Models\User;
use App\Services\TenantContext;

class CategoriePolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool
    {
        return $this->tenant->estDefini();
    }

    public function view(User $user, Categorie $categorie): bool
    {
        return $this->tenant->estDefini();
    }

    public function create(User $user): bool
    {
        return $this->tenant->aLaPermission('menu.creer');
    }

    public function update(User $user, Categorie $categorie): bool
    {
        return $this->tenant->aLaPermission('menu.modifier');
    }

    public function delete(User $user, Categorie $categorie): bool
    {
        return $this->tenant->aLaPermission('menu.supprimer');
    }
}