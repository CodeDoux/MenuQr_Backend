<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosteRequest;
use App\Http\Resources\PosteResource;
use App\Models\Poste;
use Illuminate\Support\Facades\Gate;

class PosteController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Poste::class);

        return PosteResource::collection(Poste::orderBy('nom')->get());
    }

    public function store(PosteRequest $request)
    {
        Gate::authorize('create', Poste::class);

        $poste = Poste::create($request->validated());

        return new PosteResource($poste);
    }

    public function update(PosteRequest $request, string $poste)
    {
        $posteModel = Poste::findOrFail($poste);
        Gate::authorize('update', $posteModel);

        $posteModel->update($request->validated());

        return new PosteResource($posteModel);
    }

    public function destroy(string $poste)
    {
        $posteModel = Poste::findOrFail($poste);
        Gate::authorize('delete', $posteModel);

        if ($posteModel->employes()->exists()) {
            return response()->json([
                'message' => 'Ce poste est encore assigné à au moins un employé.',
            ], 422);
        }

        $posteModel->delete();

        return response()->json(null, 204);
    }
}