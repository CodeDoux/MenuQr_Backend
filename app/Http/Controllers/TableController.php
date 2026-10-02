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

    /**
     * Libération manuelle uniquement (décision actée) : le paiement peut
     * arriver AVANT que les clients quittent physiquement la table — lier la
     * libération au paiement afficherait "Libre" alors que la table est
     * encore occupée. Le staff clôture donc explicitement.
     */
    public function liberer(string $salle, string $tableId)
    {
        $salleModel = Salle::findOrFail($salle);
        $table = $salleModel->tables()->findOrFail($tableId);
        Gate::authorize('update', $table);

        \Illuminate\Support\Facades\DB::transaction(function () use ($table) {
            $table->update(['statut' => 'LIBRE']);

            \App\Models\Visite::where('table_id', $table->id)
                ->where('statut', 'EN_COURS')
                ->update(['statut' => 'TERMINEE', 'date_fin' => now()]);
        });

        return new TableResource($table->fresh());
    }
}