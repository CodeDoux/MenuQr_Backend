<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOffreRequest;
use App\Http\Resources\AdminOffreResource;
use App\Models\Fonctionnalite;
use App\Models\Offre;
use Illuminate\Support\Facades\DB;

class AdminOffreController extends Controller
{
    public function index()
    {
        return AdminOffreResource::collection(
            Offre::with(['fonctionnalites', 'limites'])->orderBy('ordre_affichage')->get()
        );
    }

    public function store(AdminOffreRequest $request)
    {
        $data = $request->validated();

        $offre = DB::transaction(function () use ($data) {
            $offre = Offre::create(collect($data)->except(['fonctionnalites', 'limites'])->toArray());
            $this->synchroniserRelations($offre, $data);
            return $offre;
        });

        return new AdminOffreResource($offre->load(['fonctionnalites', 'limites']));
    }

    public function update(AdminOffreRequest $request, string $id)
    {
        $offre = Offre::findOrFail($id);
        $data = $request->validated();

        DB::transaction(function () use ($offre, $data) {
            $offre->update(collect($data)->except(['fonctionnalites', 'limites'])->toArray());
            $this->synchroniserRelations($offre, $data);
        });

        return new AdminOffreResource($offre->fresh(['fonctionnalites', 'limites']));
    }

    public function destroy(string $id)
    {
        $offre = Offre::findOrFail($id);
        $offre->delete();
        return response()->json(null, 204);
    }

    /** Les fonctionnalités sont de simples libellés côté frontend (pas un
     *  catalogue figé) — on les retrouve ou crée par leur nom. */
    private function synchroniserRelations(Offre $offre, array $data): void
    {
        $fonctionnaliteIds = collect($data['fonctionnalites'] ?? [])->map(
            fn (string $nom) => Fonctionnalite::firstOrCreate(['nom' => $nom], ['code' => \Illuminate\Support\Str::slug($nom)])->id
        );
        $offre->fonctionnalites()->sync($fonctionnaliteIds);

        $offre->limites()->delete();
        foreach ($data['limites'] ?? [] as $limite) {
            $offre->limites()->create($limite);
        }
    }
}