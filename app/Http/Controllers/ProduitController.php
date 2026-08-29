<?php

namespace App\Http\Controllers;

use App\Enums\StatutProduit;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProduitRequest;
use App\Http\Resources\ProduitResource;
use App\Models\Produit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * ⚠️ Resolution manuelle de Produit (voir MenuController pour le pourquoi).
 */
class ProduitController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Produit::class);

        $produits = Produit::where('statut', StatutProduit::ACTIF)
            ->with(['categories', 'variantes', 'images'])
            ->get();

        return ProduitResource::collection($produits);
    }

    public function store(ProduitRequest $request)
    {
        Gate::authorize('create', Produit::class);

        $data = $request->validated();

        $produit = DB::transaction(function () use ($data) {
            $produit = Produit::create([
                'nom' => $data['nom'],
                'description' => $data['description'] ?? null,
                'prix' => $data['prix'],
                'est_disponible' => $data['est_disponible'],
                'est_visible' => $data['est_visible'],
                'est_populaire' => $data['est_populaire'],
                'temps_preparation' => $data['temps_preparation'] ?? null,
                'statut' => StatutProduit::ACTIF,
            ]);

            $produit->categories()->sync($data['categorie_ids']);

            foreach ($data['variantes'] ?? [] as $variante) {
                $produit->variantes()->create($variante);
            }

            foreach ($data['images'] ?? [] as $image) {
                $produit->images()->create($image);
            }

            return $produit;
        });

        return new ProduitResource($produit->load(['categories', 'variantes', 'images']));
    }

    public function show(string $produit)
    {
        $produitModel = Produit::findOrFail($produit);
        Gate::authorize('view', $produitModel);

        return new ProduitResource($produitModel->load(['categories', 'variantes', 'images']));
    }

    public function update(ProduitRequest $request, string $produit)
    {
        $produitModel = Produit::findOrFail($produit);
        Gate::authorize('update', $produitModel);

        $data = $request->validated();

        DB::transaction(function () use ($produitModel, $data) {
            $produitModel->update([
                'nom' => $data['nom'],
                'description' => $data['description'] ?? null,
                'prix' => $data['prix'],
                'est_disponible' => $data['est_disponible'],
                'est_visible' => $data['est_visible'],
                'est_populaire' => $data['est_populaire'],
                'temps_preparation' => $data['temps_preparation'] ?? null,
            ]);

            $produitModel->categories()->sync($data['categorie_ids']);

            $produitModel->variantes()->delete();
            foreach ($data['variantes'] ?? [] as $variante) {
                $produitModel->variantes()->create($variante);
            }

            $produitModel->images()->delete();
            foreach ($data['images'] ?? [] as $image) {
                $produitModel->images()->create($image);
            }
        });

        return new ProduitResource($produitModel->load(['categories', 'variantes', 'images']));
    }

    public function archiver(string $produit)
    {
        $produitModel = Produit::findOrFail($produit);
        Gate::authorize('delete', $produitModel);

        $produitModel->update(['statut' => StatutProduit::ARCHIVE]);

        return response()->json(null, 204);
    }
}