<?php

namespace App\Policies;

use App\Models\User;
use App\Services\TenantContext;

class JournalActivitePolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool { return $this->tenant->aLaPermission('journal.consulter'); }
}