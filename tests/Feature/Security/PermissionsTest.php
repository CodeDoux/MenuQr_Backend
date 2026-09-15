<?php

use App\Models\Commande;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

/** Crée un restaurant + un employé avec le rôle demandé, renvoie [restaurant, token]. */
function creerStaffAvecRole(string $codeRole): array
{
    $restaurant = Restaurant::create([
        'nom' => 'Restaurant Test '.uniqid(),
        'adresse' => 'Adresse test', 'telephone' => '770000000', 'statut' => 'ACTIF',
    ]);

    $user = User::create([
        'nom_complet' => ucfirst(strtolower($codeRole)).' Test', 'email' => strtolower($codeRole).'-'.uniqid().'@test.com',
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

test('un Cuisinier peut faire avancer le statut d\'une commande en cuisine', function () {
    [$restaurant, $token] = creerStaffAvecRole('CUISINIER');

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'EN_ATTENTE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/commandes/{$commande->id}/avancer-statut-cuisine")
        ->assertOk();
});

test('un Cuisinier ne peut PAS encaisser un paiement (réservé à Propriétaire/Caissier)', function () {
    [$restaurant, $token] = creerStaffAvecRole('CUISINIER');

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'PRETE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/commandes/{$commande->id}/encaisser-direct", ['methode' => 'ESPECES'])
        ->assertForbidden();
});

test('un Cuisinier ne peut PAS gérer les employés', function () {
    [$restaurant, $token] = creerStaffAvecRole('CUISINIER');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/employes')
        ->assertForbidden();
});

test('un Caissier peut encaisser un paiement', function () {
    [$restaurant, $token] = creerStaffAvecRole('CAISSIER');

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'PRETE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/commandes/{$commande->id}/encaisser-direct", ['methode' => 'ESPECES'])
        ->assertCreated();
});

test('un Caissier ne peut PAS faire avancer le statut d\'une commande en cuisine', function () {
    [$restaurant, $token] = creerStaffAvecRole('CAISSIER');

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'EN_ATTENTE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/commandes/{$commande->id}/avancer-statut-cuisine")
        ->assertForbidden();
});

test('un Serveur peut faire avancer le statut d\'une commande', function () {
    [$restaurant, $token] = creerStaffAvecRole('SERVEUR');

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'EN_ATTENTE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/commandes/{$commande->id}/avancer-statut-cuisine")
        ->assertOk();
});

test('un Serveur ne peut PAS encaisser un paiement', function () {
    [$restaurant, $token] = creerStaffAvecRole('SERVEUR');

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'PRETE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/commandes/{$commande->id}/encaisser-direct", ['methode' => 'ESPECES'])
        ->assertForbidden();
});

test('un Serveur ne peut PAS voir la liste des employés', function () {
    [$restaurant, $token] = creerStaffAvecRole('SERVEUR');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/employes')
        ->assertForbidden();
});

test('un Livreur peut accéder aux livraisons', function () {
    [$restaurant, $token] = creerStaffAvecRole('LIVREUR');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/livraisons')
        ->assertOk();
});

test('un Livreur peut voir la liste générale des commandes', function () {
    [$restaurant, $token] = creerStaffAvecRole('LIVREUR');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/commandes')
        ->assertOk();
});

test('un Livreur ne peut PAS faire avancer le statut d\'une commande en cuisine', function () {
    [$restaurant, $token] = creerStaffAvecRole('LIVREUR');

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'EN_ATTENTE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/commandes/{$commande->id}/avancer-statut-cuisine")
        ->assertForbidden();
});