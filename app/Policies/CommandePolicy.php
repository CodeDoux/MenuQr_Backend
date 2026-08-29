<?php

namespace App\Policies;

use App\Models\Commande;
use App\Models\User;
use App\Services\TenantContext;

class CommandePolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function voir(User $user): bool
    {
        return $this->tenant->aLaPermission('commande.voir');
    }

    public function gererStatut(User $user, Commande $commande): bool
    {
        return $this->tenant->aLaPermission('commande.gerer_statut');
    }

    public function annuler(User $user, Commande $commande): bool
    {
        return $this->tenant->aLaPermission('commande.annuler');
    }

    public function encaisser(User $user): bool
    {
        return $this->tenant->aLaPermission('paiement.effectuer');
    }

    public function rembourser(User $user): bool
    {
        return $this->tenant->aLaPermission('paiement.rembourser');
    }

    public function consulterFactures(User $user): bool
    {
        return $this->tenant->aLaPermission('facture.consulter');
    }
}