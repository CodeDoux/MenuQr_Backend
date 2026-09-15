<?php

use App\Models\Notification;
use App\Models\Produit;
use App\Models\QRCode;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerRestaurantAvecStaffRole(string $codeRole): array
{
    $restaurant = Restaurant::create([
        'nom' => 'Restaurant Test '.uniqid(),
        'adresse' => 'Adresse test', 'telephone' => '770000000', 'statut' => 'ACTIF',
    ]);
    $user = User::create([
        'nom_complet' => ucfirst(strtolower($codeRole)), 'email' => strtolower($codeRole).'-'.uniqid().'@test.com',
        'password' => Hash::make('password'), 'statut' => 'ACTIF',
    ]);
    $role = Role::where('code', $codeRole)->firstOrFail();
    RestaurantUtilisateur::create([
        'restaurant_id' => $restaurant->id, 'utilisateur_id' => $user->id,
        'role_id' => $role->id, 'statut' => 'ACTIF', 'date_acceptation' => now(),
    ]);
    $token = $user->createToken('test');
    $token->accessToken->forceFill(['restaurant_id' => $restaurant->id])->save();

    return [$restaurant, $user, $token->plainTextToken];
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('une nouvelle commande notifie le Propriétaire (qui a commande.gerer_statut)', function () {
    [$restaurant, $proprietaire, $tokenProprio] = creerRestaurantAvecStaffRole('PROPRIETAIRE');

    $qrCode = QRCode::create([
        'restaurant_id' => $restaurant->id, 'table_id' => null, 'code' => 'CODE'.uniqid(),
        'url' => 'http://test/m', 'image' => 'http://test/qr.svg', 'type' => 'EMPORTER',
        'nombre_scan' => 0, 'est_actif' => true,
    ]);
    $produit = Produit::create([
        'restaurant_id' => $restaurant->id, 'nom' => 'Plat', 'prix' => 1500,
        'est_disponible' => true, 'est_visible' => true, 'est_populaire' => false, 'statut' => 'ACTIF',
    ]);

    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'EMPORTER',
        'items' => [['produit_id' => $produit->id, 'quantite' => 1]],
        'nom_client' => 'Client', 'telephone_client' => '770000001',
    ])->assertCreated();

    $notif = Notification::where('utilisateur_id', $proprietaire->id)
        ->where('type', 'NOUVELLE_COMMANDE')->first();

    expect($notif)->not->toBeNull();
});

test('un Livreur (sans commande.gerer_statut) n\'est PAS notifié des nouvelles commandes', function () {
    [$restaurant, $livreur, $tokenLivreur] = creerRestaurantAvecStaffRole('LIVREUR');

    $qrCode = QRCode::create([
        'restaurant_id' => $restaurant->id, 'table_id' => null, 'code' => 'CODE'.uniqid(),
        'url' => 'http://test/m', 'image' => 'http://test/qr.svg', 'type' => 'EMPORTER',
        'nombre_scan' => 0, 'est_actif' => true,
    ]);
    $produit = Produit::create([
        'restaurant_id' => $restaurant->id, 'nom' => 'Plat', 'prix' => 1500,
        'est_disponible' => true, 'est_visible' => true, 'est_populaire' => false, 'statut' => 'ACTIF',
    ]);

    $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'EMPORTER',
        'items' => [['produit_id' => $produit->id, 'quantite' => 1]],
        'nom_client' => 'Client', 'telephone_client' => '770000001',
    ])->assertCreated();

    $notif = Notification::where('utilisateur_id', $livreur->id)
        ->where('type', 'NOUVELLE_COMMANDE')->first();

    expect($notif)->toBeNull();
});

test('marquer un produit en rupture notifie le Propriétaire', function () {
    [$restaurant, $proprietaire, $token] = creerRestaurantAvecStaffRole('PROPRIETAIRE');

    $menu = \App\Models\Menu::create([
        'restaurant_id' => $restaurant->id, 'nom' => 'Menu', 'ordre_affichage' => 1, 'est_actif' => true,
    ]);
    $categorie = $menu->categories()->create([
        'nom' => 'Plats', 'ordre_affichage' => 1, 'est_active' => true,
    ]);

    $produit = Produit::create([
        'restaurant_id' => $restaurant->id, 'nom' => 'Plat', 'prix' => 1500,
        'est_disponible' => true, 'est_visible' => true, 'est_populaire' => false, 'statut' => 'ACTIF',
    ]);
    $produit->categories()->sync([$categorie->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/produits/{$produit->id}", [
            'nom' => 'Plat', 'prix' => 1500, 'categorie_ids' => [$categorie->id],
            'est_disponible' => false, 'est_visible' => true, 'est_populaire' => false,
        ])->assertOk();

    $notif = Notification::where('utilisateur_id', $proprietaire->id)
        ->where('type', 'STOCK_RUPTURE')->first();

    expect($notif)->not->toBeNull();
});