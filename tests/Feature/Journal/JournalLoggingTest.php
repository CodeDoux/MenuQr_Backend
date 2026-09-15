<?php

use App\Models\Commande;
use App\Models\JournalActivite;
use App\Models\Poste;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function creerRestaurantAvecProprietaireEtToken(): array
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

    return [$restaurant, $user, $token->plainTextToken];
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('faire avancer le statut d\'une commande crée une entrée dans le journal', function () {
    [$restaurant, $user, $token] = creerRestaurantAvecProprietaireEtToken();

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'EN_ATTENTE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/commandes/{$commande->id}/avancer-statut-cuisine")
        ->assertOk();

    $this->assertDatabaseHas('journal_activite', [
        'action' => 'changement_statut_commande', 'table_cible' => 'commandes', 'id_cible' => $commande->id,
    ]);
});

test('encaisser une commande directe crée une entrée journal de type paiement', function () {
    [$restaurant, $user, $token] = creerRestaurantAvecProprietaireEtToken();

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'PRETE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/commandes/{$commande->id}/encaisser-direct", ['methode' => 'ESPECES'])
        ->assertCreated();

    $this->assertDatabaseHas('journal_activite', ['action' => 'paiement', 'table_cible' => 'paiements']);
});

test('rembourser un paiement crée une entrée journal', function () {
    [$restaurant, $user, $token] = creerRestaurantAvecProprietaireEtToken();

    $commande = Commande::withoutGlobalScope(\App\Models\Scopes\RestaurantScope::class)->create([
        'restaurant_id' => $restaurant->id, 'mode' => 'EMPORTER', 'statut' => 'PRETE',
        'sous_total' => 2000, 'frais_livraison' => 0, 'remise' => 0, 'total' => 2000,
    ]);
    $repEncaissement = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/commandes/{$commande->id}/encaisser-direct", ['methode' => 'ESPECES']);
    $paiementId = $repEncaissement->json('data.id');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/paiements/{$paiementId}/rembourser")
        ->assertOk();

    $this->assertDatabaseHas('journal_activite', ['action' => 'remboursement', 'id_cible' => $paiementId]);
});

test('créer un employé crée une entrée journal', function () {
    [$restaurant, $user, $token] = creerRestaurantAvecProprietaireEtToken();

    $poste = Poste::create(['restaurant_id' => $restaurant->id, 'nom' => 'Serveur']);

    $reponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/employes', [
            'nom_complet' => 'Nouvel Employé', 'email' => 'employe-'.uniqid().'@test.com',
            'poste_id' => $poste->id, 'statut' => 'ACTIF', 'accorder_acces' => false,
        ]);

    $reponse->assertCreated();

    $this->assertDatabaseHas('journal_activite', [
        'action' => 'creation_employe', 'table_cible' => 'employes', 'id_cible' => $reponse->json('data.id'),
    ]);
});