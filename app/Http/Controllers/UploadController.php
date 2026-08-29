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
     * ⚠️ Stockage via l'abstraction Storage de Laravel : disque "public" en
     * développement, facilement remplaçable par S3 en production via .env
     * (FILESYSTEM_DISK=s3) sans changer une ligne de ce contrôleur.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $chemin = $request->file('file')->store("produits/{$this->tenant->restaurantId}", 'public');
        $url = Storage::disk('public')->url($chemin);

        return response()->json(['url' => $url]);
    }
}