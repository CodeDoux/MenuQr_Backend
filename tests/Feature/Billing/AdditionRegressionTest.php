<?php

use App\Models\Produit;
use App\Models\QRCode;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerContexteAvecTable(): array
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

    $salle = Salle::create([
        'restaurant_id' => $restaurant->id, 'description' => 'Salle test', 'ordre' => 1, 'statut' => 'ACTIVE',
    ]);
    $table = $salle->tables()->create(['numero' => '1', 'capacite' => 4, 'statut' => 'LIBRE']);
    $qrCode = QRCode::create([
        'restaurant_id' => $restaurant->id, 'table_id' => $table->id, 'code' => 'TESTCODE'.uniqid(),
        'url' => 'http://test/m', 'image' => 'http://test/qr.svg', 'type' => 'TABLE',
        'nombre_scan' => 0, 'est_actif' => true,
    ]);

    return [$restaurant, $token->plainTextToken, $qrCode];
}

function creerProduitTest(Restaurant $restaurant, string $nom, float $prix): Produit
{
    return Produit::create([
        'restaurant_id' => $restaurant->id, 'nom' => $nom, 'prix' => $prix,
        'est_disponible' => true, 'est_visible' => true, 'est_populaire' => false, 'statut' => 'ACTIF',
    ]);
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('une addition déjà payée n\'est jamais refacturée lors d\'une nouvelle commande sur la même visite', function () {
    [$restaurant, $token, $qrCode] = creerContexteAvecTable();
    $produit1 = creerProduitTest($restaurant, 'Plat 1', 3000);

    // --- Commande n°1 (3000F) ---
    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'SUR_PLACE',
        'items' => [['produit_id' => $produit1->id, 'quantite' => 1]],
    ])->assertCreated();

    $repAdditions1 = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/additions');
    $additionId1 = $repAdditions1->json('data.0.id');
    expect((float) $repAdditions1->json('data.0.total'))->toBe(3000.0);

    // Encaisse l'addition n°1 — devient PAYEE
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/additions/{$additionId1}/encaisser", ['methode' => 'ESPECES'])
        ->assertCreated();

    // --- Commande n°2 (2000F), sur la MÊME visite (table jamais libérée) ---
    $produit2 = creerProduitTest($restaurant, 'Plat 2', 2000);
    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'SUR_PLACE',
        'items' => [['produit_id' => $produit2->id, 'quantite' => 1]],
    ])->assertCreated();

    // La NOUVELLE addition ouverte doit contenir UNIQUEMENT la commande n°2
    // (2000F) — jamais 3000+2000=5000 (c'était exactement le bug corrigé).
    $repAdditions2 = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/additions');
    expect($repAdditions2->json('data'))->toHaveCount(1);
    expect((float) $repAdditions2->json('data.0.total'))->toBe(2000.0);
});

test('deux commandes non payées sur la même visite sont bien cumulées dans UNE SEULE addition', function () {
    [$restaurant, $token, $qrCode] = creerContexteAvecTable();
    $produit1 = creerProduitTest($restaurant, 'Plat 1', 3000);
    $produit2 = creerProduitTest($restaurant, 'Plat 2', 2000);

    // Deux commandes, aucune payée entre les deux
    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'SUR_PLACE',
        'items' => [['produit_id' => $produit1->id, 'quantite' => 1]],
    ])->assertCreated();

    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'SUR_PLACE',
        'items' => [['produit_id' => $produit2->id, 'quantite' => 1]],
    ])->assertCreated();

    // Comportement NORMAL et voulu : une seule addition, cumulant les deux (5000F)
    $reponse = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/additions');
    expect($reponse->json('data'))->toHaveCount(1);
    expect((float) $reponse->json('data.0.total'))->toBe(5000.0);
});