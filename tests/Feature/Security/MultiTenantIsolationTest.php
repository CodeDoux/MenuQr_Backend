<?php

use App\Models\Commande;
use App\Models\Livraison;
use App\Models\Paiement;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);
/**
 * Crée un restaurant complet avec un Propriétaire authentifié, et renvoie
 * [$restaurant, $tokenPlainText] — le vrai token HTTP, pas Sanctum::actingAs(),
 * pour exercer le VRAI pipeline de middleware (EnsureRestaurantAccess,
 * RestaurantScope) exactement comme en production.
 */
function creerRestaurantAvecProprietaire(): array
{
    $restaurant = Restaurant::create([
        'nom' => 'Restaurant Test '.uniqid(),
        'adresse' => 'Adresse test', 'telephone' => '770000000', 'statut' => 'ACTIF',
    ]);

    $user = User::create([
        'nom_complet' => 'Propriétaire Test', 'email' => 'proprio-'.uniqid().'@test.com',
        'password' => Hash::make('password'), 'statut' => 'ACTIF',
    ]);

    $rolePropio = Role::where('code', 'PROPRIETAIRE')->firstOrFail();

    RestaurantUtilisateur::create([
        'restaurant_id' => $restaurant->id, 'utilisateur_id' => $user->id,
        'role_id' => $rolePropio->id, 'statut' => 'ACTIF', 'date_acceptation' => now(),
    ]);

    $token = $user->createToken('test');
    $token->accessToken->forceFill(['restaurant_id' => $restaurant->id])->save();

    return [$restaurant, $token->plainTextToken];
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('un restaurant ne voit jamais les paiements d\'un autre restaurant', function () {
    [$restaurantA, $tokenA] = creerRestaurantAvecProprietaire();
    [$restaurantB, $tokenB] = creerRestaurantAvecProprietaire();

    // Commande + Paiement appartenant à B
    $commandeB = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurantB->id, 'mode' => 'EMPORTER', 'statut' => 'PRETE',
        'sous_total' => 5000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 5000,
    ]);
    Paiement::create([
        'type' => 'COMMANDE', 'commande_id' => $commandeB->id,
        'montant' => 5000, 'devise' => 'FCFA', 'methode' => 'ESPECES',
        'statut' => 'CONFIRME', 'date_paiement' => now(),
    ]);

    // A consulte SES paiements — ne doit JAMAIS voir celui de B
    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")
        ->getJson('/api/paiements');

    $reponse->assertOk();
    expect($reponse->json('data'))->toBeEmpty();
});

test('un restaurant ne peut pas rembourser le paiement d\'un autre restaurant', function () {
    [$restaurantA, $tokenA] = creerRestaurantAvecProprietaire();
    [$restaurantB, $tokenB] = creerRestaurantAvecProprietaire();

    $commandeB = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurantB->id, 'mode' => 'EMPORTER', 'statut' => 'PRETE',
        'sous_total' => 3000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 3000,
    ]);
    $paiementB = Paiement::create([
        'type' => 'COMMANDE', 'commande_id' => $commandeB->id,
        'montant' => 3000, 'devise' => 'FCFA', 'methode' => 'ESPECES',
        'statut' => 'CONFIRME', 'date_paiement' => now(),
    ]);

    // A tente de rembourser le paiement de B → doit échouer (404, pas trouvé pour ce tenant)
    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")
        ->patchJson("/api/paiements/{$paiementB->id}/rembourser");

    $reponse->assertNotFound();

    // Le paiement de B doit rester intact (toujours CONFIRME, pas REMBOURSE)
    expect($paiementB->fresh()->statut->value)->toBe('CONFIRME');
});

test('un restaurant ne voit jamais les livraisons d\'un autre restaurant', function () {
    [$restaurantA, $tokenA] = creerRestaurantAvecProprietaire();
    [$restaurantB, $tokenB] = creerRestaurantAvecProprietaire();

    $commandeB = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurantB->id, 'mode' => 'LIVRAISON', 'statut' => 'EN_ATTENTE',
        'sous_total' => 4000, 'frais_livraison' => 500, 'remise' => 0, 'total' => 4500,
    ]);
    $adresse = \App\Models\AdresseLivraison::create(['client_id' => null, 'adresse_complete' => 'Adresse B']);
    Livraison::create([
        'commande_id' => $commandeB->id, 'adresse_id' => $adresse->id,
        'nom_client' => 'Client B', 'telephone_client' => '770000001',
        'statut' => 'EN_ATTENTE_AFFECTATION',
    ]);

    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")
        ->getJson('/api/livraisons');

    $reponse->assertOk();
    expect($reponse->json('data'))->toBeEmpty();
});

test('un restaurant peut bien voir SES PROPRES paiements (le scope ne bloque pas tout)', function () {
    [$restaurant, $token] = creerRestaurantAvecProprietaire();

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'PRETE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);
    Paiement::create([
        'type' => 'COMMANDE', 'commande_id' => $commande->id,
        'montant' => 2000, 'devise' => 'FCFA', 'methode' => 'ESPECES',
        'statut' => 'CONFIRME', 'date_paiement' => now(),
    ]);

    $reponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/paiements');

    $reponse->assertOk();
    expect($reponse->json('data'))->toHaveCount(1);
});