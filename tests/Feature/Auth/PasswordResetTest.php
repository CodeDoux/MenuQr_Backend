<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('flux complet : demande de réinitialisation puis changement effectif du mot de passe', function () {
    $user = User::create([
        'nom_complet' => 'Test', 'email' => 'oubli@test.com',
        'password' => Hash::make('ancienMotDePasse'), 'statut' => 'ACTIF',
    ]);
    $restaurant = \App\Models\Restaurant::create([
        'nom' => 'R', 'adresse' => 'A', 'telephone' => '770000000', 'statut' => 'ACTIF',
    ]);
    $role = \App\Models\Role::where('code', 'PROPRIETAIRE')->firstOrFail();
    \App\Models\RestaurantUtilisateur::create([
        'restaurant_id' => $restaurant->id, 'utilisateur_id' => $user->id,
        'role_id' => $role->id, 'statut' => 'ACTIF', 'date_acceptation' => now(),
    ]);

    $repDemande = $this->postJson('/api/auth/mot-de-passe-oublie', ['email' => 'oubli@test.com']);
    $repDemande->assertOk();
    $resetUrl = $repDemande->json('reset_url');
    expect($resetUrl)->not->toBeNull();

    parse_str(parse_url($resetUrl, PHP_URL_QUERY), $params);
    $token = $params['token'];

    $this->postJson('/api/auth/reinitialiser-mot-de-passe', [
        'email' => 'oubli@test.com', 'token' => $token,
        'nouveau_mot_de_passe' => 'nouveauMotDePasse123',
        'nouveau_mot_de_passe_confirmation' => 'nouveauMotDePasse123',
    ])->assertOk();

    // L'ancien mot de passe ne fonctionne plus
    $this->postJson('/api/auth/login', [
        'email' => 'oubli@test.com', 'password' => 'ancienMotDePasse',
    ])->assertUnprocessable();

    // Le nouveau mot de passe fonctionne
    $this->postJson('/api/auth/login', [
        'email' => 'oubli@test.com', 'password' => 'nouveauMotDePasse123',
    ])->assertOk();
});

test('un token de réinitialisation invalide est rejeté', function () {
    User::create([
        'nom_complet' => 'Test', 'email' => 'oubli2@test.com',
        'password' => Hash::make('motdepasse'), 'statut' => 'ACTIF',
    ]);

    $this->postJson('/api/auth/mot-de-passe-oublie', ['email' => 'oubli2@test.com'])->assertOk();

    $this->postJson('/api/auth/reinitialiser-mot-de-passe', [
        'email' => 'oubli2@test.com', 'token' => 'un-token-completement-invalide',
        'nouveau_mot_de_passe' => 'nouveauMotDePasse123',
        'nouveau_mot_de_passe_confirmation' => 'nouveauMotDePasse123',
    ])->assertUnprocessable();
});

test('demander une réinitialisation pour un email inexistant ne révèle pas si le compte existe', function () {
    $reponse = $this->postJson('/api/auth/mot-de-passe-oublie', ['email' => 'inexistant@test.com']);

    $reponse->assertOk();
    expect($reponse->json('reset_url'))->toBeNull();
    // Même message générique que pour un email existant — ne confirme ni n'infirme
    expect($reponse->json('message'))->toContain('Si un compte existe');
});

test('changer son mot de passe avec le mauvais mot de passe actuel échoue', function () {
    $user = User::create([
        'nom_complet' => 'Test', 'email' => 'test-'.uniqid().'@test.com',
        'password' => Hash::make('bonmotdepasse'), 'statut' => 'ACTIF',
    ]);
    $restaurant = \App\Models\Restaurant::create([
        'nom' => 'R', 'adresse' => 'A', 'telephone' => '770000000', 'statut' => 'ACTIF',
    ]);
    $role = \App\Models\Role::where('code', 'PROPRIETAIRE')->firstOrFail();
    \App\Models\RestaurantUtilisateur::create([
        'restaurant_id' => $restaurant->id, 'utilisateur_id' => $user->id,
        'role_id' => $role->id, 'statut' => 'ACTIF', 'date_acceptation' => now(),
    ]);
    $token = $user->createToken('test');
    $token->accessToken->forceFill(['restaurant_id' => $restaurant->id])->save();

    $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
        ->putJson('/api/auth/mot-de-passe', [
            'mot_de_passe_actuel' => 'mauvaismotdepasse',
            'nouveau_mot_de_passe' => 'nouveauMotDePasse123',
            'nouveau_mot_de_passe_confirmation' => 'nouveauMotDePasse123',
        ])->assertUnprocessable();
});