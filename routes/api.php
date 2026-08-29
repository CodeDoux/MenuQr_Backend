<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\HoraireController;
use App\Http\Controllers\LivraisonController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OffreController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PosteController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\MoyenPaiementController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\PublicCommandeController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ZoneLivraisonController;
use Illuminate\Support\Facades\Route;

// --- Public ---
Route::get('/offres', [OffreController::class, 'index']);
 
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
 
Route::get('/invitations/{employeId}', [InvitationController::class, 'show']);
Route::post('/invitations/{employeId}/accepter', [InvitationController::class, 'accepter'])->middleware('throttle:6,1');
 
// --- Zone client publique (menu numérique + commande) ---
Route::prefix('public')->group(function () {
    Route::get('/menu', [PublicMenuController::class, 'show']);
    Route::post('/commandes', [PublicCommandeController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/commandes/{id}', [PublicCommandeController::class, 'show']);
    Route::get('/commandes/{id}/visite', [PublicCommandeController::class, 'commandesDeLaVisite']);
    Route::post('/commandes/{id}/payer', [PublicCommandeController::class, 'payer'])->middleware('throttle:10,1');
});
 
// --- Authentifié, sans contexte restaurant encore résolu ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/select-restaurant', [AuthController::class, 'selectRestaurant']);
});
 
// --- Authentifié + contexte restaurant actif requis ---
Route::middleware(['auth:sanctum', 'restaurant.access'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
 
    Route::apiResource('menus', MenuController::class)->except(['destroy']);
    Route::delete('/menus/{menu}', [MenuController::class, 'destroy']);
 
    Route::get('/menus/{menu}/categories', [CategorieController::class, 'index']);
    Route::post('/menus/{menu}/categories', [CategorieController::class, 'store']);
    Route::put('/menus/{menu}/categories/{categorieId}', [CategorieController::class, 'update']);
    Route::delete('/menus/{menu}/categories/{categorieId}', [CategorieController::class, 'destroy']);
 
    Route::apiResource('produits', ProduitController::class)->except(['destroy']);
    Route::patch('/produits/{produit}/archiver', [ProduitController::class, 'archiver']);
 
    Route::post('/uploads/images', [UploadController::class, 'uploadImage']);
 
    Route::get('/salles', [SalleController::class, 'index']);
    Route::post('/salles', [SalleController::class, 'store']);
    Route::put('/salles/{salle}', [SalleController::class, 'update']);
    Route::delete('/salles/{salle}', [SalleController::class, 'destroy']);
 
    Route::get('/salles/{salle}/tables', [TableController::class, 'index']);
    Route::post('/salles/{salle}/tables', [TableController::class, 'store']);
    Route::put('/salles/{salle}/tables/{tableId}', [TableController::class, 'update']);
    Route::delete('/salles/{salle}/tables/{tableId}', [TableController::class, 'destroy']);
 
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
});

