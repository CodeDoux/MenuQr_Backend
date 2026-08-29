<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategorieRequest;
use App\Http\Resources\CategorieResource;
use App\Models\Categorie;
use App\Models\Menu;
use Illuminate\Support\Facades\Gate;

/**
 * ⚠️ Resolution manuelle de Menu (voir MenuController pour le pourquoi).
 * Categorie est toujours accedee via $menu->categories() — jamais
 * Categorie::findOrFail() directement, puisqu'elle n'a pas de restaurant_id
 * propre a proteger (isolation transitive via son Menu parent, deja scope).
 */
class CategorieController extends Controller
{
    public function index(string $menu)
    {
        $menuModel = Menu::findOrFail($menu);
        Gate::authorize('viewAny', Categorie::class);

        $categories = $menuModel->categories()->orderBy('ordre_affichage')->get();

        return CategorieResource::collection($categories);
    }

    public function store(CategorieRequest $request, string $menu)
    {
        $menuModel = Menu::findOrFail($menu);
        Gate::authorize('create', Categorie::class);

        $categorie = $menuModel->categories()->create($request->validated());

        return new CategorieResource($categorie);
    }

    public function update(CategorieRequest $request, string $menu, string $categorieId)
    {
        $menuModel = Menu::findOrFail($menu);
        $categorie = $menuModel->categories()->findOrFail($categorieId);
        Gate::authorize('update', $categorie);

        $categorie->update($request->validated());

        return new CategorieResource($categorie);
    }

    public function destroy(string $menu, string $categorieId)
    {
        $menuModel = Menu::findOrFail($menu);
        $categorie = $menuModel->categories()->findOrFail($categorieId);
        Gate::authorize('delete', $categorie);

        $categorie->delete();

        return response()->json(null, 204);
    }
}
>