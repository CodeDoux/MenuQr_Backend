<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * Téléverse une image (produit, logo, etc.) et retourne son URL publique.
     * ⚠️ Stockage via l'abstraction Storage de Laravel, SANS préciser de
     * disque explicitement — utilise celui défini par FILESYSTEM_DISK dans
     * .env (désormais "r2", Cloudflare R2). Changer de fournisseur de
     * stockage ne demandera jamais de toucher ce fichier.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $chemin = $request->file('file')->store("produits/{$this->tenant->restaurantId}");
        $url = Storage::url($chemin);

        return response()->json(['url' => $url]);
    }
}