<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ZoneLivraisonRequest;
use App\Http\Resources\ZoneLivraisonResource;
use App\Models\ZoneLivraison;
use Illuminate\Support\Facades\Gate;

class ZoneLivraisonController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', ZoneLivraison::class);
        return ZoneLivraisonResource::collection(ZoneLivraison::orderBy('nom')->get());
    }

    public function store(ZoneLivraisonRequest $request)
    {
        Gate::authorize('create', ZoneLivraison::class);
        $zone = ZoneLivraison::create($request->validated());
        return new ZoneLivraisonResource($zone);
    }

    public function update(ZoneLivraisonRequest $request, string $zone)
    {
        $zoneModel = ZoneLivraison::findOrFail($zone);
        Gate::authorize('update', $zoneModel);
        $zoneModel->update($request->validated());
        return new ZoneLivraisonResource($zoneModel);
    }

    public function destroy(string $zone)
    {
        $zoneModel = ZoneLivraison::findOrFail($zone);
        Gate::authorize('delete', $zoneModel);
        $zoneModel->delete();
        return response()->json(null, 204);
    }
}