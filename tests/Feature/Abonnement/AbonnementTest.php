<?php

use App\Models\Abonnement;
use App\Models\Offre;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerRestaurantAvecAbonnement(string $codeRole = 'PROPRIETAIRE'): array
{
    $restaurant = Restaurant::create([
        'nom' => 'Restaurant Test '.uniqid(),
        'adresse' => 'Adresse test', 'telephone' => '770000000', 'statut' => 'ACTIF',
    ]);
    $user = User::create([
        'nom_complet' => 'Test', 'email' => strtolower($codeRole).'-'.uniqid().'@test.com',
        'password' => Hash::make('password'), 'statut' => 'ACTIF',
    ]);
    $role = Role::where('code', $codeRole)->firstOrFail();
    RestaurantUtilisateur::create([
        'restaurant_id' => $restaurant->id, 'utilisateur_id' => $user->id,
        'role_id' => $role->id, 'statut' => 'ACTIF', 'date_acceptation' => now(),
    ]);
    $token = $user->createToken('test');
    $token->accessToken->forceFill(['restaurant_id' => $restaurant->id])->save();

    $offre = Offre::create([
        'nom' => 'Offre Test', 'prix_mensuel' => 15000, 'duree_essai' => 14,
        'statut' => 'ACTIF', 'ordre_affichage' => 1,
    ]);
    $abonnement = Abonnement::create([
        'restaurant_id' => $restaurant->id, 'offre_id' => $offre->id,
        'date_debut' => now(), 'date_fin' => now()->addDays(14),
        'statut' => 'ESSAI', 'renouvellement_automatique' => false,
    ]);

    return [$restaurant, $token->plainTextToken, $abonnement, $offre];
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('le Propriétaire peut consulter son abonnement', function () {
    [$restaurant, $token] = creerRestaurantAvecAbonnement('PROPRIETAIRE');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/abonnement')
        ->assertOk();
});

test('un Cuisinier ne peut PAS annuler l\'abonnement', function () {
    [$restaurant, $token] = creerRestaurantAvecAbonnement('CUISINIER');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/abonnement/annuler')
        ->assertForbidden();
});

test('le Propriétaire peut annuler puis réactiver son abonnement', function () {
    [$restaurant, $token, $abonnement] = creerRestaurantAvecAbonnement('PROPRIETAIRE');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/abonnement/annuler')
        ->assertOk();
    expect($abonnement->fresh()->statut->value)->toBe('ANNULE');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/abonnement/reactiver')
        ->assertOk();
    expect($abonnement->fresh()->statut->value)->toBe('ACTIF');
});

test('réactiver un abonnement qui n\'est pas annulé échoue', function () {
    [$restaurant, $token, $abonnement] = creerRestaurantAvecAbonnement('PROPRIETAIRE');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/abonnement/reactiver')
        ->assertStatus(422);
});

test('changer d\'offre met bien à jour l\'abonnement', function () {
    [$restaurant, $token, $abonnement, $ancienneOffre] = creerRestaurantAvecAbonnement('PROPRIETAIRE');

    $nouvelleOffre = Offre::create([
        'nom' => 'Offre Premium', 'prix_mensuel' => 30000, 'duree_essai' => 14,
        'statut' => 'ACTIF', 'ordre_affichage' => 2,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson('/api/abonnement', ['offre_id' => $nouvelleOffre->id])
        ->assertOk();

    expect($abonnement->fresh()->offre_id)->toBe($nouvelleOffre->id);
});