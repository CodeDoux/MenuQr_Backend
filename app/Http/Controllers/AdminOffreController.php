<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOffreRequest;
use App\Http\Resources\AdminOffreResource;
use App\Models\Fonctionnalite;
use App\Models\Offre;
use App\Services\AdminJournalService;
use Illuminate\Support\Facades\DB;

class AdminOffreController extends Controller
{
    public function __construct(private readonly AdminJournalService $journal) {}

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

        $this->journal->enregistrer(
            request()->user()->id,
            'creation_offre',
            'offres',
            $offre->id,
            null,
            ['nom' => $offre->nom, 'prix_mensuel' => $offre->prix_mensuel]
        );

        return new AdminOffreResource($offre->load(['fonctionnalites', 'limites']));
    }

    public function update(AdminOffreRequest $request, string $id)
    {
        $offre = Offre::findOrFail($id);
        $ancienneValeur = ['nom' => $offre->nom, 'prix_mensuel' => $offre->prix_mensuel];
        $data = $request->validated();

        DB::transaction(function () use ($offre, $data) {
            $offre->update(collect($data)->except(['fonctionnalites', 'limites'])->toArray());
            $this->synchroniserRelations($offre, $data);
        });

        $offre->refresh();
        $this->journal->enregistrer(
            request()->user()->id,
            'modification_offre',
            'offres',
            $id,
            $ancienneValeur,
            ['nom' => $offre->nom, 'prix_mensuel' => $offre->prix_mensuel]
        );

        return new AdminOffreResource($offre->fresh(['fonctionnalites', 'limites']));
    }

    public function destroy(string $id)
    {
        $offre = Offre::findOrFail($id);
        $ancienneValeur = ['nom' => $offre->nom];

        $offre->delete();

        $this->journal->enregistrer(
            request()->user()->id,
            'suppression_offre',
            'offres',
            $id,
            $ancienneValeur,
            null
        );

        return response()->json(null, 204);
    }

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