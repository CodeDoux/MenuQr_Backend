<?php

use App\Models\Produit;
use App\Models\QRCode;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\Salle;
use App\Models\User;
use App\Models\Visite;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerRestaurantTableEtToken(): array
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

    $salle = Salle::create(['restaurant_id' => $restaurant->id, 'description' => 'Salle', 'ordre' => 1, 'statut' => 'ACTIVE']);
    $table = $salle->tables()->create(['numero' => '1', 'capacite' => 4, 'statut' => 'LIBRE']);
    $qrCode = QRCode::create([
        'restaurant_id' => $restaurant->id, 'table_id' => $table->id, 'code' => 'TESTCODE'.uniqid(),
        'url' => 'http://test/m', 'image' => 'http://test/qr.svg', 'type' => 'TABLE',
        'nombre_scan' => 0, 'est_actif' => true,
    ]);
    $produit = Produit::create([
        'restaurant_id' => $restaurant->id, 'nom' => 'Plat', 'prix' => 2000,
        'est_disponible' => true, 'est_visible' => true, 'est_populaire' => false, 'statut' => 'ACTIF',
    ]);

    return [$restaurant, $salle, $table, $qrCode, $produit, $token->plainTextToken];
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('une table est LIBRE tant qu\'aucune commande n\'a été passée', function () {
    [$restaurant, $salle, $table] = creerRestaurantTableEtToken();

    expect($table->fresh()->statut->value)->toBe('LIBRE');
});

test('une table passe à OCCUPEE dès la première commande sur place', function () {
    [$restaurant, $salle, $table, $qrCode, $produit] = creerRestaurantTableEtToken();

    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'SUR_PLACE',
        'items' => [['produit_id' => $produit->id, 'quantite' => 1]],
    ])->assertCreated();

    expect($table->fresh()->statut->value)->toBe('OCCUPEE');
});

test('libérer la table la remet à LIBRE et clôture la visite en cours', function () {
    [$restaurant, $salle, $table, $qrCode, $produit, $token] = creerRestaurantTableEtToken();

    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'SUR_PLACE',
        'items' => [['produit_id' => $produit->id, 'quantite' => 1]],
    ])->assertCreated();

    expect($table->fresh()->statut->value)->toBe('OCCUPEE');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/salles/{$salle->id}/tables/{$table->id}/liberer")
        ->assertOk();

    expect($table->fresh()->statut->value)->toBe('LIBRE');

    $visite = Visite::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)
        ->where('table_id', $table->id)->first();
    expect($visite->statut->value)->toBe('TERMINEE');
});

test('après libération, une nouvelle commande démarre une NOUVELLE visite (pas de réutilisation de l\'ancienne)', function () {
    [$restaurant, $salle, $table, $qrCode, $produit, $token] = creerRestaurantTableEtToken();

    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'SUR_PLACE',
        'items' => [['produit_id' => $produit->id, 'quantite' => 1]],
    ])->assertCreated();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/salles/{$salle->id}/tables/{$table->id}/liberer")
        ->assertOk();

    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'SUR_PLACE',
        'items' => [['produit_id' => $produit->id, 'quantite' => 1]],
    ])->assertCreated();

    $visites = Visite::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)
        ->where('table_id', $table->id)->get();

    expect($visites)->toHaveCount(2);
    expect($visites->first()->statut->value)->toBe('TERMINEE');
    expect($visites->last()->statut->value)->toBe('EN_COURS');
    expect($table->fresh()->statut->value)->toBe('OCCUPEE');
});