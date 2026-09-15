<?php

use App\Models\Poste;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerRestaurantProprioPourInvitation(): array
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

test('flux complet : création employé avec accès, consultation puis acceptation de l\'invitation', function () {
    [$restaurant, $tokenProprio] = creerRestaurantProprioPourInvitation();
    $poste = Poste::create(['restaurant_id' => $restaurant->id, 'nom' => 'Serveur']);

    $repCreation = $this->withHeader('Authorization', "Bearer {$tokenProprio}")
        ->postJson('/api/employes', [
            'nom_complet' => 'Nouvel Employé', 'email' => 'nouvel-'.uniqid().'@test.com',
            'poste_id' => $poste->id, 'statut' => 'ACTIF',
            'accorder_acces' => true, 'role' => 'SERVEUR',
        ]);
    $repCreation->assertCreated();
    $employeId = $repCreation->json('data.id');

    // Consultation publique (pas de token, route publique)
    $repShow = $this->getJson("/api/invitations/{$employeId}");
    $repShow->assertOk();
    expect($repShow->json('role'))->toBe('SERVEUR');

    // Acceptation
    $repAccepter = $this->postJson("/api/invitations/{$employeId}/accepter", [
        'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123',
    ]);
    $repAccepter->assertOk();
    expect($repAccepter->json('role'))->toBe('SERVEUR');
    $repAccepter->assertJsonStructure(['token']);
});

test('consulter une invitation déjà acceptée échoue (404)', function () {
    [$restaurant, $tokenProprio] = creerRestaurantProprioPourInvitation();
    $poste = Poste::create(['restaurant_id' => $restaurant->id, 'nom' => 'Serveur']);

    $repCreation = $this->withHeader('Authorization', "Bearer {$tokenProprio}")
        ->postJson('/api/employes', [
            'nom_complet' => 'Employé', 'email' => 'employe-'.uniqid().'@test.com',
            'poste_id' => $poste->id, 'statut' => 'ACTIF',
            'accorder_acces' => true, 'role' => 'CUISINIER',
        ]);
    $employeId = $repCreation->json('data.id');

    $this->postJson("/api/invitations/{$employeId}/accepter", [
        'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123',
    ])->assertOk();

    // Deuxième consultation, après acceptation → doit échouer
    $this->getJson("/api/invitations/{$employeId}")->assertNotFound();
});

test('une invitation avec un id inexistant renvoie 404', function () {
    $this->getJson('/api/invitations/00000000-0000-0000-0000-000000000000')->assertNotFound();
});