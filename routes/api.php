<?php

use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminOffreController;
use App\Http\Controllers\AdminRestaurantController;
use App\Http\Controllers\AdminUtilisateurController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\HoraireController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LivraisonController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MoyenPaiementController;
use App\Http\Controllers\OffreController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PosteController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PublicCommandeController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\ZoneLivraisonController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StatistiquesController;

// --- Public ---
Route::get('/offres', [OffreController::class, 'index'])->middleware('throttle:60,1');

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/mot-de-passe-oublie', [AuthController::class, 'motDePasseOublie'])->middleware('throttle:6,1');
Route::post('/auth/reinitialiser-mot-de-passe', [AuthController::class, 'reinitialiserMotDePasse'])->middleware('throttle:6,1');

Route::get('/invitations/{employeId}', [InvitationController::class, 'show'])->middleware('throttle:30,1');
Route::post('/invitations/{employeId}/accepter', [InvitationController::class, 'accepter'])->middleware('throttle:6,1');

// --- Zone client publique (menu numérique + commande) ---
Route::prefix('public')->group(function () {
    Route::get('/menu', [PublicMenuController::class, 'show'])->middleware('throttle:60,1');
    Route::post('/commandes', [PublicCommandeController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/commandes/{id}', [PublicCommandeController::class, 'show'])->middleware('throttle:60,1');
    Route::get('/commandes/{id}/visite', [PublicCommandeController::class, 'commandesDeLaVisite'])->middleware('throttle:60,1');
    Route::post('/commandes/{id}/payer', [PublicCommandeController::class, 'payer'])->middleware('throttle:10,1');
});

// --- Authentifié, sans contexte restaurant encore résolu ---
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/auth/select-restaurant', [AuthController::class, 'selectRestaurant']);
});

// --- Authentifié + contexte restaurant actif requis ---
Route::middleware(['auth:sanctum', 'restaurant.access', 'throttle:120,1'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/mot-de-passe', [AuthController::class, 'changerMotDePasse']);
    Route::put('/auth/profil', [AuthController::class, 'modifierProfil']);

    Route::apiResource('menus', MenuController::class)->except(['destroy']);
    Route::delete('/menus/{menu}', [MenuController::class, 'destroy']);

    Route::get('/menus/{menu}/categories', [CategorieController::class, 'index']);
    Route::post('/menus/{menu}/categories', [CategorieController::class, 'store']);
    Route::put('/menus/{menu}/categories/{categorieId}', [CategorieController::class, 'update']);
    Route::delete('/menus/{menu}/categories/{categorieId}', [CategorieController::class, 'destroy']);

    Route::apiResource('produits', ProduitController::class)->except(['destroy']);
    Route::patch('/produits/{produit}/archiver', [ProduitController::class, 'archiver']);
    Route::patch('/produits/{id}/disponibilite', [ProduitController::class, 'basculerDisponibilite']);

    Route::post('/uploads/images', [UploadController::class, 'uploadImage']);

    Route::get('/salles', [SalleController::class, 'index']);
    Route::post('/salles', [SalleController::class, 'store']);
    Route::put('/salles/{salle}', [SalleController::class, 'update']);
    Route::delete('/salles/{salle}', [SalleController::class, 'destroy']);

    Route::get('/salles/{salle}/tables', [TableController::class, 'index']);
    Route::post('/salles/{salle}/tables', [TableController::class, 'store']);
    Route::put('/salles/{salle}/tables/{tableId}', [TableController::class, 'update']);
    Route::delete('/salles/{salle}/tables/{tableId}', [TableController::class, 'destroy']);
    Route::patch('/salles/{salle}/tables/{tableId}/liberer', [TableController::class, 'liberer']);

    Route::post('/salles/{salle}/tables/{tableId}/qrcode', [QrCodeController::class, 'genererPourTable']);
    Route::post('/qrcodes/generales', [QrCodeController::class, 'genererGeneral']);
    Route::get('/qrcodes', [QrCodeController::class, 'index']);
    Route::patch('/qrcodes/{id}/desactiver', [QrCodeController::class, 'desactiver']);

    Route::get('/postes', [PosteController::class, 'index']);
    Route::post('/postes', [PosteController::class, 'store']);
    Route::put('/postes/{poste}', [PosteController::class, 'update']);
    Route::delete('/postes/{poste}', [PosteController::class, 'destroy']);

    Route::get('/employes', [EmployeController::class, 'index']);
    Route::post('/employes', [EmployeController::class, 'store']);
    Route::put('/employes/{employe}', [EmployeController::class, 'update']);
    Route::patch('/employes/{employe}/terminer', [EmployeController::class, 'terminer']);
    Route::post('/employes/{employe}/renvoyer-invitation', [EmployeController::class, 'renvoyerInvitation']);

    // --- Commandes / Paiements / Factures ---
    Route::get('/commandes', [CommandeController::class, 'index']);
    Route::patch('/commandes/{id}/avancer-statut-cuisine', [CommandeController::class, 'avancerStatutCuisine']);
    Route::patch('/commandes/{id}/terminer', [CommandeController::class, 'terminer']);
    Route::patch('/commandes/{id}/annuler', [CommandeController::class, 'annuler']);

    Route::get('/additions', [PaiementController::class, 'additionsOuvertes']);
    Route::get('/additions/{id}', [PaiementController::class, 'addition']);
    Route::post('/additions/{id}/encaisser', [PaiementController::class, 'encaisserAddition']);
    Route::post('/commandes/{id}/encaisser-direct', [PaiementController::class, 'encaisserCommandeDirecte']);

    Route::get('/paiements', [PaiementController::class, 'index']);
    Route::patch('/paiements/{id}/rembourser', [PaiementController::class, 'rembourser']);

    Route::get('/factures', [PaiementController::class, 'factures']);
    Route::get('/factures/{id}', [PaiementController::class, 'facture']);

    Route::get('/zones-livraison', [ZoneLivraisonController::class, 'index']);
    Route::post('/zones-livraison', [ZoneLivraisonController::class, 'store']);
    Route::put('/zones-livraison/{zone}', [ZoneLivraisonController::class, 'update']);
    Route::delete('/zones-livraison/{zone}', [ZoneLivraisonController::class, 'destroy']);

    Route::get('/livraisons', [LivraisonController::class, 'index']);
    Route::post('/livraisons/{id}/affecter', [LivraisonController::class, 'affecter']);
    Route::patch('/livraisons/{id}/avancer-statut', [LivraisonController::class, 'avancerStatut']);
    Route::patch('/livraisons/{id}/annuler', [LivraisonController::class, 'annuler']);

    Route::get('/horaires', [HoraireController::class, 'index']);
    Route::put('/horaires/{id}', [HoraireController::class, 'update']);

    Route::get('/moyens-paiement', [MoyenPaiementController::class, 'index']);
    Route::put('/moyens-paiement/{id}', [MoyenPaiementController::class, 'update']);

    Route::get('/promotions', [PromotionController::class, 'index']);
    Route::post('/promotions', [PromotionController::class, 'store']);
    Route::put('/promotions/{id}', [PromotionController::class, 'update']);
    Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);

    Route::get('/journal', [JournalController::class, 'index']);

    Route::get('/abonnement', [AbonnementController::class, 'show']);
    Route::put('/abonnement', [AbonnementController::class, 'changerOffre']);
    Route::patch('/abonnement/annuler', [AbonnementController::class, 'annuler']);
    Route::patch('/abonnement/reactiver', [AbonnementController::class, 'reactiver']);
    Route::get('/factures-abonnement', [AbonnementController::class, 'factures']);

    Route::get('/statistiques/dashboard', [StatistiquesController::class, 'dashboard']);
    Route::get('/statistiques/detaillees', [StatistiquesController::class, 'detaillees']);

    Route::get('/restaurant', [RestaurantController::class, 'show']);
    Route::put('/restaurant', [RestaurantController::class, 'update']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/lue', [NotificationController::class, 'marquerLue']);
    Route::patch('/notifications/tout-marquer-lu', [NotificationController::class, 'toutMarquerLu']);
});

// ============================================================
// ADMIN MENUQR — authentification et middleware totalement séparés
// du reste de l'API (jamais de restaurant.access, jamais de TenantContext).
// ============================================================
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware(['auth:sanctum', 'admin', 'throttle:120,1'])->prefix('admin')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);

    Route::get('/restaurants', [AdminRestaurantController::class, 'index']);
    Route::patch('/restaurants/{id}/statut', [AdminRestaurantController::class, 'changerStatut']);

    Route::get('/offres', [AdminOffreController::class, 'index']);
    Route::post('/offres', [AdminOffreController::class, 'store']);
    Route::put('/offres/{id}', [AdminOffreController::class, 'update']);
    Route::delete('/offres/{id}', [AdminOffreController::class, 'destroy']);

    Route::get('/administrateurs', [AdminUtilisateurController::class, 'index']);
    Route::post('/administrateurs', [AdminUtilisateurController::class, 'store']);
    Route::patch('/administrateurs/{id}/toggle-actif', [AdminUtilisateurController::class, 'toggleActif']);
    Route::delete('/administrateurs/{id}', [AdminUtilisateurController::class, 'destroy']);
});