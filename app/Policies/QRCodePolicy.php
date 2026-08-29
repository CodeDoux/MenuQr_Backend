<?php

namespace App\Policies;

use App\Models\QRCode;
use App\Models\User;
use App\Services\TenantContext;

class QRCodePolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool
    {
        return $this->tenant->estDefini();
    }

    public function create(User $user): bool
    {
        return $this->tenant->aLaPermission('table.gerer');
    }

    public function delete(User $user, QRCode $qrCode): bool
    {
        return $this->tenant->aLaPermission('table.gerer');
    }
}