<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalleRequest;
use App\Http\Resources\SalleResource;
use App\Models\Salle;
use Illuminate\Support\Facades\Gate;

/** ⚠️ Resolution manuelle (voir MenuController pour le pourquoi). */
class SalleController extends Controller
{
    public function index()
{
    Gate::authorize('viewAny', Salle::class);

    $salles = Salle::withCount('tables')->orderBy('ordre')->get();

    \Log::info('Salles renvoyées', [
        'restaurant_ids_trouves' => $salles->pluck('restaurant_id')->unique()->values(),
    ]);

    return SalleResource::collection($salles);
}

    public function store(SalleRequest $request)
    {
        Gate::authorize('create', Salle::class);

        $salle = Salle::create($request->validated());

        return new SalleResource($salle);
    }

    public function update(SalleRequest $request, string $salle)
    {
        $salleModel = Salle::findOrFail($salle);
        Gate::authorize('update', $salleModel);

        $salleModel->update($request->validated());

        return new SalleResource($salleModel);
    }

    public function destroy(string $salle)
    {
        $salleModel = Salle::findOrFail($salle);
        Gate::authorize('delete', $salleModel);

        $salleModel->delete(); // cascade : supprime aussi ses tables (contrainte DB)

        return response()->json(null, 204);
    }
}