<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\HoraireRequest;
use App\Http\Resources\HoraireResource;
use App\Models\Horaire;
use Illuminate\Support\Facades\Gate;

class HoraireController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Horaire::class);
        return HoraireResource::collection(Horaire::orderByRaw(
            "array_position(ARRAY['LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE'], jour_semaine)"
        )->get());
    }

    public function update(HoraireRequest $request, string $id)
    {
        $horaire = Horaire::findOrFail($id);
        Gate::authorize('update', $horaire);
        $horaire->update($request->validated());
        return new HoraireResource($horaire);
    }
}
