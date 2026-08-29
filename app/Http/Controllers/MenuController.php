<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use Illuminate\Support\Facades\Gate;

/**
 * ⚠️ Utilise volontairement des IDs bruts (string) plutôt que le route model
 * binding implicite de Laravel : ce dernier peut s'exécuter AVANT que le
 * middleware EnsureRestaurantAccess ait fini de définir le contexte tenant
 * (ordre d'exécution entre SubstituteBindings et un middleware custom non
 * garanti), ce qui ferait échouer RestaurantScope. La résolution manuelle
 * ici s'exécute dans le corps de la méthode, donc toujours après le middleware.
 */
class MenuController extends Controller
{
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

        return new MenuResource($menuModel);
    }

    public function destroy(string $menu)
    {
        $menuModel = Menu::findOrFail($menu);
        Gate::authorize('delete', $menuModel);

        $menuModel->delete();

        return response()->json(null, 204);
    }
}