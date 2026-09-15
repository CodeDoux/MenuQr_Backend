<?php

use App\Models\QRCode;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerRestaurantAvecTableEtToken(): array
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

    return [$restaurant, $salle, $table, $token->plainTextToken];
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('générer un QR code pour une table fonctionne', function () {
    [$restaurant, $salle, $table, $token] = creerRestaurantAvecTableEtToken();

    $reponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/salles/{$salle->id}/tables/{$table->id}/qrcode");

    $reponse->assertCreated();
    $this->assertDatabaseHas('qr_codes', ['restaurant_id' => $restaurant->id, 'table_id' => $table->id, 'est_actif' => true]);
});

test('régénérer un QR code désactive automatiquement l\'ancien', function () {
    [$restaurant, $salle, $table, $token] = creerRestaurantAvecTableEtToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/salles/{$salle->id}/tables/{$table->id}/qrcode")->assertCreated();
    $ancienId = QRCode::where('table_id', $table->id)->where('est_actif', true)->first()->id;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/salles/{$salle->id}/tables/{$table->id}/qrcode")->assertCreated();

    $ancien = QRCode::find($ancienId);
    expect($ancien->est_actif)->toBeFalse();

    $actifs = QRCode::where('table_id', $table->id)->where('est_actif', true)->get();
    expect($actifs)->toHaveCount(1);
});

test('un restaurant ne voit jamais les QR codes d\'un autre restaurant', function () {
    [$restaurantA, $salleA, $tableA, $tokenA] = creerRestaurantAvecTableEtToken();
    [$restaurantB, $salleB, $tableB, $tokenB] = creerRestaurantAvecTableEtToken();

    $this->withHeader('Authorization', "Bearer {$tokenB}")
        ->postJson("/api/salles/{$salleB->id}/tables/{$tableB->id}/qrcode")->assertCreated();

    \Illuminate\Support\Facades\Auth::forgetGuards();

    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")->getJson('/api/qrcodes');

    $reponse->assertOk();
    expect($reponse->json('data'))->toBeEmpty();
});

test('un restaurant ne peut pas désactiver le QR code d\'un autre restaurant', function () {
    [$restaurantA, $salleA, $tableA, $tokenA] = creerRestaurantAvecTableEtToken();
    [$restaurantB, $salleB, $tableB, $tokenB] = creerRestaurantAvecTableEtToken();

    $this->withHeader('Authorization', "Bearer {$tokenB}")
        ->postJson("/api/salles/{$salleB->id}/tables/{$tableB->id}/qrcode")->assertCreated();
    $qrCodeB = QRCode::where('table_id', $tableB->id)->where('est_actif', true)->first();
        \Illuminate\Support\Facades\Auth::forgetGuards();

    $reponse = $this->withHeader('Authorization', "Bearer {$tokenA}")
        ->patchJson("/api/qrcodes/{$qrCodeB->id}/desactiver");

    $reponse->assertNotFound();
    expect($qrCodeB->fresh()->est_actif)->toBeTrue();
});