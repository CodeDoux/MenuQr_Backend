<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Services\MenuPublicCacheService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Gate;

/**
 * ⚠️ Utilise volontairement des IDs bruts (string) plutôt que le route model
 * binding implicite de Laravel : ce dernier peut s'exécuter AVANT que le
 * middleware EnsureRestaurantAccess ait fini de définir le contexte tenant.
 */
class MenuController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly MenuPublicCacheService $cache
    ) {}

    public function index()
    {
        Gate::authorize('viewAny', Menu::class);

        $menus = Menu::withCount('categories')->orderBy('ordre_affichage')->get();

        return MenuResource::collection($menus);
    }

    public function store(MenuRequest $request)
    {
        Gate::authorize('create', Menu::class);

        $menu = Menu::create($request->validated());

        $this->cache->invalider($this->tenant->restaurantId);
        return new MenuResource($menu);
    }

    public function show(string $menu)
    {
        $menuModel = Menu::findOrFail($menu);
        Gate::authorize('view', $menuModel);

        return new MenuResource($menuModel->loadCount('categories'));
    }

    public function update(MenuRequest $request, string $menu)
    {
        $menuModel = Menu::findOrFail($menu);
        Gate::authorize('update', $menuModel);

        $menuModel->update($request->validated());

        $this->cache->invalider($this->tenant->restaurantId);
        return new MenuResource($menuModel);
    }

    public function destroy(string $menu)
    {
        $menuModel = Menu::findOrFail($menu);
        Gate::authorize('delete', $menuModel);

        $menuModel->delete();

        $this->cache->invalider($this->tenant->restaurantId);
        return response()->json(null, 204);
    }
}