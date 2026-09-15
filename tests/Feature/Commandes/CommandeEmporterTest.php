<?php

use App\Models\Produit;
use App\Models\QRCode;
use App\Models\Restaurant;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerQrGeneralEmporter(): array
{
    $restaurant = Restaurant::create([
        'nom' => 'Restaurant Test '.uniqid(),
        'adresse' => 'Adresse test', 'telephone' => '770000000', 'statut' => 'ACTIF',
    ]);
    $qrCode = QRCode::create([
        'restaurant_id' => $restaurant->id, 'table_id' => null, 'code' => 'EMPORTER'.uniqid(),
        'url' => 'http://test/m', 'image' => 'http://test/qr.svg', 'type' => 'EMPORTER',
        'nombre_scan' => 0, 'est_actif' => true,
    ]);
    $produit = Produit::create([
        'restaurant_id' => $restaurant->id, 'nom' => 'Plat', 'prix' => 2500,
        'est_disponible' => true, 'est_visible' => true, 'est_populaire' => false, 'statut' => 'ACTIF',
    ]);

    return [$restaurant, $qrCode, $produit];
}

test('une commande à emporter SANS nom/téléphone est rejetée', function () {
    [$restaurant, $qrCode, $produit] = creerQrGeneralEmporter();

    $reponse = $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'EMPORTER',
        'items' => [['produit_id' => $produit->id, 'quantite' => 1]],
    ]);

    $reponse->assertUnprocessable();
    $reponse->assertJsonValidationErrors(['nom_client', 'telephone_client']);
});

test('une commande à emporter AVEC nom/téléphone/heure de retrait est acceptée et stockée', function () {
    [$restaurant, $qrCode, $produit] = creerQrGeneralEmporter();

    $reponse = $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'EMPORTER',
        'items' => [['produit_id' => $produit->id, 'quantite' => 1]],
        'nom_client' => 'Awa Diop', 'telephone_client' => '771234567',
        'heure_retrait_souhaitee' => '14:30',
    ]);

    $reponse->assertCreated();
    expect($reponse->json('data.nom_client'))->toBe('Awa Diop');
    expect($reponse->json('data.telephone_client'))->toBe('771234567');
    expect($reponse->json('data.heure_retrait_souhaitee'))->not->toBeNull();
});

test('une heure de retrait mal formatée est rejetée', function () {
    [$restaurant, $qrCode, $produit] = creerQrGeneralEmporter();

    $reponse = $this->postJson('/api/public/commandes', [
        'code' => $qrCode->code, 'mode' => 'EMPORTER',
        'items' => [['produit_id' => $produit->id, 'quantite' => 1]],
        'nom_client' => 'Awa Diop', 'telephone_client' => '771234567',
        'heure_retrait_souhaitee' => 'demain matin',
    ]);

    $reponse->assertUnprocessable();
    $reponse->assertJsonValidationErrors(['heure_retrait_souhaitee']);
});