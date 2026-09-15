<?php

namespace App\Http\Controllers;

use App\Enums\StatutProduit;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProduitRequest;
use App\Http\Resources\ProduitResource;
use App\Models\Notification;
use App\Models\Produit;
use App\Models\RestaurantUtilisateur;
use App\Services\MenuPublicCacheService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProduitController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly MenuPublicCacheService $cache
    ) {}

    public function index()
    {
        Gate::authorize('viewAny', Produit::class);

        $query = Produit::where('statut', StatutProduit::ACTIF)
            ->with(['categories', 'variantes', 'images']);

        if ($recherche = request()->query('recherche')) {
            $query->where('nom', 'ILIKE', "%{$recherche}%");
        }

        if ($categorieId = request()->query('categorie_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categorieId));
        }

        if (request()->has('page') || request()->has('per_page')) {
            return ProduitResource::collection($query->paginate(request()->integer('per_page', 24)));
        }

        return ProduitResource::collection($query->get());
    }

    public function store(ProduitRequest $request)
    {
        Gate::authorize('create', Produit::class);

        $data = $request->validated();

        $produit = DB::transaction(function () use ($data) {
            $produit = Produit::create([
                'nom' => $data['nom'],
                'description' => $data['description'] ?? null,
                'prix' => $data['prix'],
                'est_disponible' => $data['est_disponible'],
                'est_visible' => $data['est_visible'],
                'est_populaire' => $data['est_populaire'],
                'temps_preparation' => $data['temps_preparation'] ?? null,
                'statut' => StatutProduit::ACTIF,
            ]);

            $produit->categories()->sync($data['categorie_ids']);

            foreach ($data['variantes'] ?? [] as $variante) {
                $produit->variantes()->create($variante);
            }

            foreach ($data['images'] ?? [] as $image) {
                $produit->images()->create($image);
            }

            return $produit;
        });

        $this->cache->invalider($this->tenant->restaurantId);
        return new ProduitResource($produit->load(['categories', 'variantes', 'images']));
    }

    public function show(string $produit)
    {
        $produitModel = Produit::findOrFail($produit);
        Gate::authorize('view', $produitModel);

        return new ProduitResource($produitModel->load(['categories', 'variantes', 'images']));
    }

    public function update(ProduitRequest $request, string $produit)
    {
        $produitModel = Produit::findOrFail($produit);
        Gate::authorize('update', $produitModel);

        $data = $request->validated();
        $etaitDisponible = $produitModel->est_disponible;

        DB::transaction(function () use ($produitModel, $data) {
            $produitModel->update([
                'nom' => $data['nom'],
                'description' => $data['description'] ?? null,
                'prix' => $data['prix'],
                'est_disponible' => $data['est_disponible'],
                'est_visible' => $data['est_visible'],
                'est_populaire' => $data['est_populaire'],
                'temps_preparation' => $data['temps_preparation'] ?? null,
            ]);

            $produitModel->categories()->sync($data['categorie_ids']);

            $produitModel->variantes()->delete();
            foreach ($data['variantes'] ?? [] as $variante) {
                $produitModel->variantes()->create($variante);
            }

            $produitModel->images()->delete();
            foreach ($data['images'] ?? [] as $image) {
                $produitModel->images()->create($image);
            }
        });

        if ($etaitDisponible && ! $produitModel->est_disponible) {
            $this->notifierRupture($produitModel);
        }

        $this->cache->invalider($this->tenant->restaurantId);
        return new ProduitResource($produitModel->load(['categories', 'variantes', 'images']));
    }

    public function archiver(string $produit)
    {
        $produitModel = Produit::findOrFail($produit);
        Gate::authorize('delete', $produitModel);

        $produitModel->update(['statut' => StatutProduit::ARCHIVE]);

        $this->cache->invalider($this->tenant->restaurantId);
        return response()->json(null, 204);
    }

    public function basculerDisponibilite(string $produit)
    {
        $produitModel = Produit::findOrFail($produit);
        Gate::authorize('update', $produitModel);

        $nouvelleDisponibilite = ! $produitModel->est_disponible;
        $produitModel->update(['est_disponible' => $nouvelleDisponibilite]);

        if (! $nouvelleDisponibilite) {
            $this->notifierRupture($produitModel);
        }

        $this->cache->invalider($this->tenant->restaurantId);
        return new ProduitResource($produitModel->load(['categories', 'variantes', 'images']));
    }

    private function notifierRupture(Produit $produitModel): void
    {
        $managers = RestaurantUtilisateur::where('restaurant_id', $produitModel->restaurant_id)
            ->where('statut', 'ACTIF')
            ->whereHas('role', fn ($q) => $q->whereIn('code', ['PROPRIETAIRE', 'GERANT']))
            ->get();

        foreach ($managers as $acces) {
            Notification::create([
                'utilisateur_id' => $acces->utilisateur_id,
                'titre' => 'Rupture de stock',
                'message' => "Le produit \"{$produitModel->nom}\" est marqué en rupture",
                'type' => 'STOCK_RUPTURE',
                'lien' => '/produits',
                'est_lu' => false,
                'date_envoie' => now(),
            ]);
        }
    }
}