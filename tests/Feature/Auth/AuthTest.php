<?php

use App\Models\Offre;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->offre = Offre::create([
        'nom' => 'Offre Test', 'prix_mensuel' => 0, 'duree_essai' => 14,
        'statut' => 'ACTIF', 'ordre_affichage' => 1,
    ]);
});

test('l\'inscription crée un restaurant, un propriétaire, et un abonnement en essai', function () {
    $reponse = $this->postJson('/api/auth/register', [
        'nom_complet' => 'Jean Test', 'email' => 'jean-'.uniqid().'@test.com',
        'password' => 'password123', 'password_confirmation' => 'password123',
        'restaurant_nom' => 'Le Test', 'restaurant_adresse' => 'Rue test',
        'restaurant_telephone' => '770000000', 'offre_id' => $this->offre->id,
    ]);

    $reponse->assertCreated();
    $reponse->assertJsonStructure(['token', 'user', 'restaurant', 'role', 'permissions']);
    expect($reponse->json('role'))->toBe('PROPRIETAIRE');
    expect($reponse->json('permissions'))->toContain('commande.voir');

    $this->assertDatabaseHas('restaurants', ['nom' => 'Le Test']);
    $this->assertDatabaseHas('abonnements', ['statut' => 'ESSAI']);
});

test('la connexion avec un mauvais mot de passe échoue', function () {
    $user = User::create([
        'nom_complet' => 'Test', 'email' => 'existant@test.com',
        'password' => Hash::make('bonmotdepasse'), 'statut' => 'ACTIF',
    ]);

    $reponse = $this->postJson('/api/auth/login', [
        'email' => 'existant@test.com', 'password' => 'mauvaismotdepasse',
    ]);

    $reponse->assertUnprocessable();
});

test('un compte désactivé ne peut pas se connecter même avec le bon mot de passe', function () {
    User::create([
        'nom_complet' => 'Test', 'email' => 'desactive@test.com',
        'password' => Hash::make('password123'), 'statut' => 'BLOQUE',
    ]);

    $reponse = $this->postJson('/api/auth/login', [
        'email' => 'desactive@test.com', 'password' => 'password123',
    ]);

    $reponse->assertUnprocessable();
});