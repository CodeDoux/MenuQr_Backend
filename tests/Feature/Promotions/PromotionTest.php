<?php

use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerRestaurantProprioPourPromo(): array
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

test('créer une promotion globale fonctionne', function () {
    [$restaurant, $token] = creerRestaurantProprioPourPromo();

    $reponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/promotions', [
            'nom' => 'Promo -10%', 'type_reduction' => 'POURCENTAGE', 'valeur' => 10,
            'cible' => 'COMMANDE_ENTIERE', 'est_active' => true,
            'date_debut' => now()->toDateString(), 'date_fin' => now()->addDays(30)->toDateString(),
        ]);

    $reponse->assertCreated();
    $this->assertDatabaseHas('promotions', ['nom' => 'Promo -10%', 'restaurant_id' => $restaurant->id]);
});

test('un Cuisinier ne peut PAS créer de promotion', function () {
    [$restaurant, $tokenProprio] = creerRestaurantProprioPourPromo();

    $user = User::create([
        'nom_complet' => 'Cuisinier', 'email' => 'cuisinier-'.uniqid().'@test.com',
        'password' => Hash::make('password'), 'statut' => 'ACTIF',
    ]);
    $role = \App\Models\Role::where('code', 'CUISINIER')->firstOrFail();
    RestaurantUtilisateur::create([
        'restaurant_id' => $restaurant->id, 'utilisateur_id' => $user->id,
        'role_id' => $role->id, 'statut' => 'ACTIF', 'date_acceptation' => now(),
    ]);
    $token = $user->createToken('test');
    $token->accessToken->forceFill(['restaurant_id' => $restaurant->id])->save();

    $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
        ->postJson('/api/promotions', [
            'nom' => 'Promo', 'type_reduction' => 'POURCENTAGE', 'valeur' => 10,
            'cible' => 'COMMANDE_ENTIERE', 'est_active' => true,
            'date_debut' => now()->toDateString(), 'date_fin' => now()->addDays(30)->toDateString(),
        ])
        ->assertForbidden();
});

test('un restaurant ne voit jamais les promotions d\'un autre restaurant', function () {
    [$restaurantA, $tokenA] = creerRestaurantProprioPourPromo();
    [$restaurantB, $tokenB] = creerRestaurantProprioPourPromo();

    $this->withHeader('Authorization', "Bearer {$tokenB}")
        ->postJson('/api/promotions', [
            'nom' => 'Promo B', 'type_reduction' => 'POURCENTAGE', 'valeur' => 10,
            'cible' => 'COMMANDE_ENTIERE', 'est_active' => true,
            'date_debut' => now()->toDateString(), 'date_fin' => now()->addDays(30)->toDateString(),
        ])->assertCreated();

    \Illuminate\Support\Facades\Auth::forgetGuards();

    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")->getJson('/api/promotions');

    $reponse->assertOk();
    expect($reponse->json('data'))->toBeEmpty();
});