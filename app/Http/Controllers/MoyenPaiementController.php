<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\MoyenPaiementRequest;
use App\Http\Resources\MoyenPaiementResource;
use App\Models\MoyenPaiement;
use Illuminate\Support\Facades\Gate;

class MoyenPaiementController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', MoyenPaiement::class);
        return MoyenPaiementResource::collection(MoyenPaiement::orderBy('methode')->get());
    }

    public function update(MoyenPaiementRequest $request, string $id)
    {
        $moyen = MoyenPaiement::findOrFail($id);
        Gate::authorize('update', $moyen);
        $moyen->update($request->validated());
        return new MoyenPaiementResource($moyen);
    }
}
