<?php

use App\Models\AdresseLivraison;
use App\Models\Commande;
use App\Models\Livraison;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use App\Models\ZoneLivraison;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerRestaurantAvecLivraison(): array
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

    $zone = ZoneLivraison::create([
        'restaurant_id' => $restaurant->id, 'nom' => 'Zone 1', 'frais' => 500, 'statut' => 'ACTIVE',
    ]);
    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'LIVRAISON', 'statut' => 'EN_ATTENTE',
        'sous_total' => 3000, 'frais_livraison' => 500, 'remise' => 0, 'total' => 3500,
    ]);
    $adresse = AdresseLivraison::create(['client_id' => null, 'adresse_complete' => 'Rue test']);
    $livraison = Livraison::create([
        'commande_id' => $commande->id, 'adresse_id' => $adresse->id,
        'nom_client' => 'Client Test', 'telephone_client' => '770000001',
        'statut' => 'EN_ATTENTE_AFFECTATION', 'zone_livraison_id' => $zone->id,
    ]);

    return [$restaurant, $token->plainTextToken, $livraison];
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('une livraison démarre bien en attente d\'affectation', function () {
    [$restaurant, $token, $livraison] = creerRestaurantAvecLivraison();

    expect($livraison->statut->value)->toBe('EN_ATTENTE_AFFECTATION');
});

test('on peut affecter un livreur externe à une livraison', function () {
    [$restaurant, $token, $livraison] = creerRestaurantAvecLivraison();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/livraisons/{$livraison->id}/affecter", [
            'type' => 'PRESTATAIRE_EXTERNE', 'nom' => 'Livreur Externe', 'telephone' => '770000002',
        ])
        ->assertOk();

    expect($livraison->fresh()->statut->value)->toBe('AFFECTEE');
});

test('on peut faire avancer le statut d\'une livraison déjà affectée', function () {
    [$restaurant, $token, $livraison] = creerRestaurantAvecLivraison();
    $livraison->update(['statut' => 'AFFECTEE']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/livraisons/{$livraison->id}/avancer-statut")
        ->assertOk();

    expect($livraison->fresh()->statut->value)->toBe('RECUPEREE');
});

test('on peut annuler une livraison', function () {
    [$restaurant, $token, $livraison] = creerRestaurantAvecLivraison();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/livraisons/{$livraison->id}/annuler")
        ->assertNoContent();

    expect($livraison->fresh()->statut->value)->toBe('ANNULEE');
});