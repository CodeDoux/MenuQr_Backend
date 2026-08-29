<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionRequest;
use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use Illuminate\Support\Facades\Gate;

class PromotionController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Promotion::class);
        return PromotionResource::collection(Promotion::with('produits')->latest('created_at')->get());
    }

    public function store(PromotionRequest $request)
    {
        Gate::authorize('create', Promotion::class);

        $data = $request->validated();
        $promotion = Promotion::create([
            ...collect($data)->except('produit_ids')->toArray(),
            'nombre_utilisations' => 0,
        ]);

        if ($data['cible'] === 'PRODUIT') {
            $promotion->produits()->sync($data['produit_ids']);
        }

        return new PromotionResource($promotion->load('produits'));
    }

    public function update(PromotionRequest $request, string $id)
    {
        $promotion = Promotion::findOrFail($id);
        Gate::authorize('update', $promotion);

        $data = $request->validated();
        $promotion->update(collect($data)->except('produit_ids')->toArray());

        if ($data['cible'] === 'PRODUIT') {
            $promotion->produits()->sync($data['produit_ids']);
        } else {
            $promotion->produits()->sync([]);
        }

        return new PromotionResource($promotion->fresh('produits'));
    }

    public function destroy(string $id)
    {
        $promotion = Promotion::findOrFail($id);
        Gate::authorize('delete', $promotion);
        $promotion->delete();
        return response()->json(null, 204);
    }
}
