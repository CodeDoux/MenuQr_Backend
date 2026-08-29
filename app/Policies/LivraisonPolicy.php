<?php

namespace App\Policies;

use App\Models\Livraison;
use App\Models\User;
use App\Services\TenantContext;

class LivraisonPolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function voir(User $user): bool { return $this->tenant->aLaPermission('commande.voir'); }
    public function gerer(User $user, ?Livraison $livraison = null): bool { return $this->tenant->aLaPermission('livraison.gerer'); }
}