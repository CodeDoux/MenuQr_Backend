<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategorieRequest;
use App\Http\Resources\CategorieResource;
use App\Models\Categorie;
use App\Models\Menu;
use App\Services\MenuPublicCacheService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Gate;

class CategorieController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly MenuPublicCacheService $cache
    ) {}

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

        $this->cache->invalider($this->tenant->restaurantId);
        return new CategorieResource($categorie);
    }

    public function update(CategorieRequest $request, string $menu, string $categorieId)
    {
        $menuModel = Menu::findOrFail($menu);
        $categorie = $menuModel->categories()->findOrFail($categorieId);
        Gate::authorize('update', $categorie);

        $categorie->update($request->validated());

        $this->cache->invalider($this->tenant->restaurantId);

        return new CategorieResource($categorie);
    }

    public function destroy(string $menu, string $categorieId)
    {
        $menuModel = Menu::findOrFail($menu);
        $categorie = $menuModel->categories()->findOrFail($categorieId);
        Gate::authorize('delete', $categorie);

        $categorie->delete();

        $this->cache->invalider($this->tenant->restaurantId);

        return response()->json(null, 204);
    }
}