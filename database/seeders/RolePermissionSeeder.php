<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    private const ROLES = [
        'PROPRIETAIRE' => ['nom' => 'Propriétaire', 'niveau' => 1],
        'GERANT' => ['nom' => 'Gérant', 'niveau' => 2],
        'SERVEUR' => ['nom' => 'Serveur', 'niveau' => 3],
        'CUISINIER' => ['nom' => 'Cuisinier', 'niveau' => 3],
        'CAISSIER' => ['nom' => 'Caissier', 'niveau' => 3],
        'LIVREUR' => ['nom' => 'Livreur', 'niveau' => 3],
    ];

    private const PERMISSIONS = [
        'menu.creer' => 'Créer un menu/produit',
        'menu.modifier' => 'Modifier un menu/produit',
        'menu.supprimer' => 'Supprimer un menu',
        'commande.voir' => 'Voir les commandes',
        'commande.gerer_statut' => 'Faire progresser le statut d\'une commande',
        'commande.annuler' => 'Annuler une commande',
        'paiement.effectuer' => 'Encaisser un paiement',
        'paiement.rembourser' => 'Rembourser un paiement',
        'table.gerer' => 'Gérer salles/tables/QR codes',
        'livraison.gerer' => 'Gérer les livraisons',
        'employe.gerer' => 'Gérer les employés',
        'abonnement.gerer' => 'Gérer l\'abonnement du restaurant',
        'promotion.gerer' => 'Gérer les promotions',
        'parametre.gerer' => 'Gérer les paramètres du restaurant',
        'facture.consulter' => 'Consulter les factures',
        'journal.consulter' => 'Consulter le journal d\'activité',
        'statistique.consulter' => 'Consulter les statistiques',
    ];

    private const MATRICE = [
        'PROPRIETAIRE' => [
            'menu.creer', 'menu.modifier', 'menu.supprimer',
            'commande.voir', 'commande.gerer_statut', 'commande.annuler',
            'paiement.effectuer', 'paiement.rembourser',
            'table.gerer', 'livraison.gerer', 'employe.gerer',
            'abonnement.gerer', 'promotion.gerer', 'parametre.gerer',
            'facture.consulter', 'journal.consulter', 'statistique.consulter',
        ],
        'GERANT' => [
            'menu.creer', 'menu.modifier',
            'commande.voir', 'commande.gerer_statut', 'commande.annuler',
            'table.gerer', 'livraison.gerer', 'employe.gerer',
            'promotion.gerer', 'facture.consulter', 'statistique.consulter',
        ],
        'SERVEUR' => ['commande.voir', 'commande.gerer_statut', 'livraison.gerer'],
        'CUISINIER' => ['commande.voir', 'commande.gerer_statut'],
        'CAISSIER' => ['commande.voir', 'paiement.effectuer', 'facture.consulter'],
        'LIVREUR' => ['commande.voir', 'livraison.gerer'],
    ];

    public function run(): void
    {
        $roles = [];
        foreach (self::ROLES as $code => $info) {
            $roles[$code] = Role::updateOrCreate(
                ['code' => $code],
                ['nom' => $info['nom'], 'niveau' => $info['niveau'], 'est_systeme' => true]
            );
        }

        $permissions = [];
        foreach (self::PERMISSIONS as $code => $nom) {
            $permissions[$code] = Permission::updateOrCreate(['code' => $code], ['nom' => $nom]);
        }

        foreach (self::MATRICE as $roleCode => $permissionCodes) {
            $permissionIds = collect($permissionCodes)->map(fn (string $code) => $permissions[$code]->id)->all();
            $roles[$roleCode]->permissions()->sync($permissionIds);
        }

        $this->command->info('Rôles et permissions synchronisés selon la matrice validée.');
    }
}

>