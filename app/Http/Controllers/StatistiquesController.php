<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\Paiement;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class StatistiquesController extends Controller
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function dashboard()
    {
        if (! $this->tenant->aLaPermission('statistique.consulter')) {
            abort(403);
        }

        $restaurantId = $this->tenant->restaurantId;
        $debutJour = now()->startOfDay();
        $debutSemaine = now()->startOfWeek();

        // CA = paiements réellement confirmés (pas juste le total des commandes,
        // qui pourrait inclure des commandes pas encore payées).
        $caJour = Paiement::where('statut', 'CONFIRME')
            ->where('date_paiement', '>=', $debutJour)
            ->where(function ($q) use ($restaurantId) {
                $q->whereHas('commande', fn ($sub) => $sub->where('restaurant_id', $restaurantId))
                  ->orWhereHas('addition.visite', fn ($sub) => $sub->where('restaurant_id', $restaurantId));
            })
            ->sum('montant');

        $caSemaine = Paiement::where('statut', 'CONFIRME')
            ->where('date_paiement', '>=', $debutSemaine)
            ->where(function ($q) use ($restaurantId) {
                $q->whereHas('commande', fn ($sub) => $sub->where('restaurant_id', $restaurantId))
                  ->orWhereHas('addition.visite', fn ($sub) => $sub->where('restaurant_id', $restaurantId));
            })
            ->sum('montant');

        $nbCommandesJour = Commande::where('restaurant_id', $restaurantId)
            ->where('statut', '!=', 'ANNULEE')->where('created_at', '>=', $debutJour)->count();

        $nbCommandesSemaine = Commande::where('restaurant_id', $restaurantId)
            ->where('statut', '!=', 'ANNULEE')->where('created_at', '>=', $debutSemaine)->count();

        $topProduits = LigneCommande::select('produit_id', DB::raw('SUM(quantite) as total_quantite'))
            ->whereHas('commande', fn ($q) => $q->where('restaurant_id', $restaurantId)
                ->where('statut', '!=', 'ANNULEE')
                ->where('created_at', '>=', now()->subDays(30)))
            ->with('produit:id,nom')
            ->groupBy('produit_id')
            ->orderByDesc('total_quantite')
            ->limit(5)
            ->get()
            ->map(fn ($ligne) => ['produit_nom' => $ligne->produit?->nom, 'quantite' => (int) $ligne->total_quantite]);

        $repartitionMode = Commande::where('restaurant_id', $restaurantId)
            ->where('statut', '!=', 'ANNULEE')
            ->where('created_at', '>=', $debutSemaine)
            ->select('mode', DB::raw('COUNT(*) as total'))
            ->groupBy('mode')
            ->pluck('total', 'mode');

        return response()->json([
            'ca_jour' => (float) $caJour,
            'ca_semaine' => (float) $caSemaine,
            'nb_commandes_jour' => $nbCommandesJour,
            'nb_commandes_semaine' => $nbCommandesSemaine,
            'top_produits' => $topProduits,
            'repartition_mode' => [
                'SUR_PLACE' => $repartitionMode['SUR_PLACE'] ?? 0,
                'EMPORTER' => $repartitionMode['EMPORTER'] ?? 0,
                'LIVRAISON' => $repartitionMode['LIVRAISON'] ?? 0,
            ],
        ]);
    }

    /**
     * Statistiques détaillées (page Statistiques, complémentaire au
     * Dashboard) : évolution du CA, répartition par méthode de paiement,
     * top catégories, panier moyen, taux d'annulation, heures de pointe.
     * ⚠️ Tout est calculé à partir des données existantes — aucune nouvelle
     * règle métier, aucune nouvelle table.
     */
    public function detaillees()
    {
        if (! $this->tenant->aLaPermission('statistique.consulter')) {
            abort(403);
        }

        $restaurantId = $this->tenant->restaurantId;
        $jours = max(1, min(90, (int) request()->query('periode', 30)));
        $debut = now()->subDays($jours - 1)->startOfDay();

        $matchRestaurant = function ($q) use ($restaurantId) {
            $q->whereHas('commande', fn ($sub) => $sub->where('restaurant_id', $restaurantId))
              ->orWhereHas('addition.visite', fn ($sub) => $sub->where('restaurant_id', $restaurantId));
        };

        // --- Évolution du CA jour par jour ---
        $parJour = Paiement::selectRaw('DATE(date_paiement) as jour, SUM(montant) as total')
            ->where('statut', 'CONFIRME')
            ->where('date_paiement', '>=', $debut)
            ->where($matchRestaurant)
            ->groupBy('jour')
            ->pluck('total', 'jour');

        $evolutionCa = [];
        for ($i = 0; $i < $jours; $i++) {
            $date = $debut->copy()->addDays($i)->format('Y-m-d');
            $evolutionCa[] = ['date' => $date, 'montant' => (float) ($parJour[$date] ?? 0)];
        }

        // --- Répartition par méthode de paiement ---
        $parMethode = Paiement::selectRaw('methode, SUM(montant) as total')
            ->where('statut', 'CONFIRME')
            ->where('date_paiement', '>=', $debut)
            ->where($matchRestaurant)
            ->groupBy('methode')
            ->pluck('total', 'methode');

        // --- Top catégories (⚠️ un produit dans plusieurs catégories compte pour chacune) ---
        $lignes = \App\Models\LigneCommande::whereHas('commande', fn ($q) => $q->where('restaurant_id', $restaurantId)
                ->where('statut', '!=', 'ANNULEE')
                ->where('created_at', '>=', $debut))
            ->with('produit.categories')
            ->get();

        $compteurCategories = [];
        foreach ($lignes as $ligne) {
            foreach ($ligne->produit?->categories ?? [] as $categorie) {
                $compteurCategories[$categorie->nom] = ($compteurCategories[$categorie->nom] ?? 0) + $ligne->quantite;
            }
        }
        arsort($compteurCategories);
        $topCategories = collect(array_slice($compteurCategories, 0, 5, true))
            ->map(fn ($qte, $nom) => ['categorie_nom' => $nom, 'quantite' => $qte])
            ->values();

        // --- Panier moyen ---
        $commandesPeriode = Commande::where('restaurant_id', $restaurantId)
            ->where('statut', '!=', 'ANNULEE')
            ->where('created_at', '>=', $debut);
        $nbCommandesPeriode = $commandesPeriode->count();
        $panierMoyen = $nbCommandesPeriode > 0 ? $commandesPeriode->sum('total') / $nbCommandesPeriode : 0;

        // --- Taux d'annulation ---
        $totalPeriode = Commande::where('restaurant_id', $restaurantId)->where('created_at', '>=', $debut)->count();
        $annulees = Commande::where('restaurant_id', $restaurantId)->where('statut', 'ANNULEE')->where('created_at', '>=', $debut)->count();
        $tauxAnnulation = $totalPeriode > 0 ? round($annulees / $totalPeriode * 100, 1) : 0;

        // --- Heures de pointe ---
        $parHeure = Commande::where('restaurant_id', $restaurantId)
            ->where('statut', '!=', 'ANNULEE')
            ->where('created_at', '>=', $debut)
            ->selectRaw('EXTRACT(HOUR FROM created_at) as heure, COUNT(*) as total')
            ->groupBy('heure')
            ->pluck('total', 'heure');

        $heuresPointe = [];
        for ($h = 0; $h < 24; $h++) {
            $heuresPointe[] = ['heure' => $h, 'nb_commandes' => (int) ($parHeure[$h] ?? 0)];
        }

        return response()->json([
            'periode_jours' => $jours,
            'evolution_ca' => $evolutionCa,
            'repartition_paiement' => [
                'ESPECES' => (float) ($parMethode['ESPECES'] ?? 0),
                'WAVE' => (float) ($parMethode['WAVE'] ?? 0),
                'ORANGE_MONEY' => (float) ($parMethode['ORANGE_MONEY'] ?? 0),
                'CARTE' => (float) ($parMethode['CARTE'] ?? 0),
                'AUTRE' => (float) ($parMethode['AUTRE'] ?? 0),
            ],
            'top_categories' => $topCategories,
            'panier_moyen' => round($panierMoyen, 2),
            'taux_annulation' => $tauxAnnulation,
            'heures_pointe' => $heuresPointe,
        ]);
    }
}