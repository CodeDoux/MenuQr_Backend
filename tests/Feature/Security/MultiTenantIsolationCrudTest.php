<?php

use App\Models\Menu;
use App\Models\Poste;
use App\Models\Produit;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerRestaurantProprioToken(): array
{
    $restaurant = Restaurant::create([
        'nom' => 'Restaurant Test '.uniqid(),
        'adresse' => 'Adresse test', 'telephone' => '770000000', 'statut' => 'ACTIF',
    ]);
    $user = User::create([
        'nom_complet' => 'Propriétaire Test', 'email' => 'proprio-'.uniqid().'@test.com',
        'password' => Hash::make('password'), 'statut' => 'ACTIF',
    ]);
    $role = Role::where('code', 'PROPRIETAIRE')->firstOrFail();
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

test('un restaurant ne voit jamais les menus d\'un autre restaurant', function () {
    [$restaurantA, $tokenA] = creerRestaurantProprioToken();
    [$restaurantB, $tokenB] = creerRestaurantProprioToken();

    Menu::create(['restaurant_id' => $restaurantB->id, 'nom' => 'Menu de B', 'ordre_affichage' => 1, 'est_actif' => true]);

    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")->getJson('/api/menus');

    $reponse->assertOk();
    expect($reponse->json('data'))->toBeEmpty();
});

test('un restaurant ne voit jamais les produits d\'un autre restaurant', function () {
    [$restaurantA, $tokenA] = creerRestaurantProprioToken();
    [$restaurantB, $tokenB] = creerRestaurantProprioToken();

    Produit::create([
        'restaurant_id' => $restaurantB->id, 'nom' => 'Plat de B', 'prix' => 3000,
        'est_disponible' => true, 'est_visible' => true, 'est_populaire' => false, 'statut' => 'ACTIF',
    ]);

    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")->getJson('/api/produits');

    $reponse->assertOk();
    expect($reponse->json('data'))->toBeEmpty();
});

test('un restaurant ne voit jamais les salles d\'un autre restaurant', function () {
    [$restaurantA, $tokenA] = creerRestaurantProprioToken();
    [$restaurantB, $tokenB] = creerRestaurantProprioToken();

    Salle::create(['restaurant_id' => $restaurantB->id, 'description' => 'Salle de B', 'ordre' => 1, 'statut' => 'ACTIVE']);

    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")->getJson('/api/salles');

    $reponse->assertOk();
    expect($reponse->json('data'))->toBeEmpty();
});

test('un restaurant ne voit jamais les employés d\'un autre restaurant', function () {
    [$restaurantA, $tokenA] = creerRestaurantProprioToken();
    [$restaurantB, $tokenB] = creerRestaurantProprioToken();

    $posteB = Poste::create(['restaurant_id' => $restaurantB->id, 'nom' => 'Poste B']);
    $userB = User::create([
        'nom_complet' => 'Employé B', 'email' => 'employe-b-'.uniqid().'@test.com',
        'password' => Hash::make('password'), 'statut' => 'ACTIF',
    ]);
    \App\Models\Employe::create([
        'restaurant_id' => $restaurantB->id, 'utilisateur_id' => $userB->id,
        'poste_id' => $posteB->id, 'statut' => 'ACTIF',
    ]);

    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")->getJson('/api/employes');

    $reponse->assertOk();
    expect($reponse->json('data'))->toBeEmpty();
});

test('un restaurant ne peut pas modifier un menu d\'un autre restaurant', function () {
    [$restaurantA, $tokenA] = creerRestaurantProprioToken();
    [$restaurantB, $tokenB] = creerRestaurantProprioToken();

    $menuB = Menu::create(['restaurant_id' => $restaurantB->id, 'nom' => 'Menu B', 'ordre_affichage' => 1, 'est_actif' => true]);

    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")
        ->putJson("/api/menus/{$menuB->id}", ['nom' => 'Piraté', 'ordre_affichage' => 1, 'est_actif' => true]);

    $reponse->assertNotFound();
    expect($menuB->fresh()->nom)->toBe('Menu B');
});