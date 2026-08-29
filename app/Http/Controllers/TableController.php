<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\TableRequest;
use App\Http\Resources\TableResource;
use App\Models\Salle;
use App\Models\TableRestaurant;
use Illuminate\Support\Facades\Gate;

/** ⚠️ Resolution manuelle de Salle ; TableRestaurant toujours via $salle->tables(). */
class TableController extends Controller
{
    public function index(string $salle)
    {
        $salleModel = Salle::findOrFail($salle);
        Gate::authorize('viewAny', TableRestaurant::class);

        return TableResource::collection($salleModel->tables()->get());
    }

    public function store(TableRequest $request, string $salle)
    {
        $salleModel = Salle::findOrFail($salle);
        Gate::authorize('create', TableRestaurant::class);

        $table = $salleModel->tables()->create($request->validated());

        return new TableResource($table);
    }

    public function update(TableRequest $request, string $salle, string $tableId)
    {
        $salleModel = Salle::findOrFail($salle);
        $table = $salleModel->tables()->findOrFail($tableId);
        Gate::authorize('update', $table);

        $table->update($request->validated());

        return new TableResource($table);
    }

    public function destroy(string $salle, string $tableId)
    {
        $salleModel = Salle::findOrFail($salle);
        $table = $salleModel->tables()->findOrFail($tableId);
        Gate::authorize('delete', $table);

        $table->delete();

        return response()->json(null, 204);
    }
}