<?php

use App\Models\Commande;
use App\Models\Poste;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerStaffRole(string $codeRole): array
{
    $restaurant = Restaurant::create([
        'nom' => 'Restaurant Test '.uniqid(),
        'adresse' => 'Adresse test', 'telephone' => '770000000', 'statut' => 'ACTIF',
    ]);
    $user = User::create([
        'nom_complet' => ucfirst(strtolower($codeRole)), 'email' => strtolower($codeRole).'-'.uniqid().'@test.com',
        'password' => Hash::make('password'), 'statut' => 'ACTIF',
    ]);
    $role = Role::where('code', $codeRole)->firstOrFail();
    RestaurantUtilisateur::create([
        'restaurant_id' => $restaurant->id, 'utilisateur_id' => $user->id,
        'role_id' => $role->id, 'statut' => 'ACTIF', 'date_acceptation' => now(),
    ]);
    $token = $user->createToken('test');
    $token->accessToken->forceFill(['restaurant_id' => $restaurant->id])->save();

    return [$restaurant, $token->plainTextToken];
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('un Gérant peut voir et gérer les employés', function () {
    [$restaurant, $token] = creerStaffRole('GERANT');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/employes')
        ->assertOk();

    $poste = Poste::create(['restaurant_id' => $restaurant->id, 'nom' => 'Serveur']);
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/employes', [
            'nom_complet' => 'Nouvel Employé', 'email' => 'employe-'.uniqid().'@test.com',
            'poste_id' => $poste->id, 'statut' => 'ACTIF', 'accorder_acces' => false,
        ])
        ->assertCreated();
});

test('un Gérant peut encaisser un paiement', function () {
    [$restaurant, $token] = creerStaffRole('GERANT');

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'PRETE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/commandes/{$commande->id}/encaisser-direct", ['methode' => 'ESPECES'])
        ->assertCreated();
});

test('un Gérant peut faire avancer le statut d\'une commande', function () {
    [$restaurant, $token] = creerStaffRole('GERANT');

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'EN_ATTENTE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/commandes/{$commande->id}/avancer-statut-cuisine")
        ->assertOk();
});

test('un Gérant peut gérer les menus et produits', function () {
    [$restaurant, $token] = creerStaffRole('GERANT');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/menus', ['nom' => 'Nouveau Menu', 'ordre_affichage' => 1, 'est_actif' => true])
        ->assertCreated();
});