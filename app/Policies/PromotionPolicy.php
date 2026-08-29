<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;
use App\Services\TenantContext;

class PromotionPolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool { return $this->tenant->estDefini(); }
    public function create(User $user): bool { return $this->tenant->aLaPermission('promotion.gerer'); }
    public function update(User $user, Promotion $promotion): bool { return $this->tenant->aLaPermission('promotion.gerer'); }
    public function delete(User $user, Promotion $promotion): bool { return $this->tenant->aLaPermission('promotion.gerer'); }
}