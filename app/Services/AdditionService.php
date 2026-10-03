<?php

namespace App\Services;

use App\Models\Addition;
use App\Models\Commande;
use App\Models\Promotion;
use App\Enums\StatutAddition;
use App\Enums\StatutCommande;
use App\Models\Scopes\RestaurantScope;

class AdditionService
{
/**
     * Calcule la remise totale des promotions éligibles pour un ensemble de
     * lignes données. Réutilisée à la fois pour une commande standalone
     * (Emporter/Livraison) et pour une addition entière (Sur place).
     *
     * @param array $lignes Tableau de ['produit_id' => ..., 'quantite' => ..., 'sous_total' => ...]
     */
    public function calculerRemisePromotions(string $restaurantId, float $sousTotal, array $lignes): float
    {
        $promotionsEligibles = \App\Models\Promotion::withoutGlobalScope(RestaurantScope::class)
            ->where('restaurant_id', $restaurantId)
            ->where('est_active', true)
            ->where('date_debut', '<=', now())
            ->where(function ($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('limite_utilisation')->orWhereColumn('nombre_utilisations', '<', 'limite_utilisation');
            })
            ->with(['produits' => fn ($q) => $q->withoutGlobalScope(RestaurantScope::class)])
            ->get();

        $remiseTotale = 0;

        foreach ($promotionsEligibles as $promotion) {
            $cible = $promotion->cible instanceof \BackedEnum ? $promotion->cible->value : $promotion->cible;
            $typeReduction = $promotion->type_reduction instanceof \BackedEnum ? $promotion->type_reduction->value : $promotion->type_reduction;

            if ($cible === 'PRODUIT') {
                $produitIdsPromo = $promotion->produits->pluck('id')->all();
                foreach ($lignes as $ligne) {
                    if (in_array($ligne['produit_id'], $produitIdsPromo, true)) {
                        $remiseTotale += $typeReduction === 'POURCENTAGE'
                            ? $ligne['sous_total'] * ((float) $promotion->valeur / 100)
                            : (float) $promotion->valeur * $ligne['quantite'];
                    }
                }
            } else { // COMMANDE_ENTIERE
                $remiseTotale += $typeReduction === 'POURCENTAGE'
                    ? $sousTotal * ((float) $promotion->valeur / 100)
                    : (float) $promotion->valeur;
            }
        }

        return min($remiseTotale, $sousTotal);
    }


     /**
     * ⚠️ CORRIGÉ — l'ancienne version resommait TOUTES les commandes de la
     * visite à chaque appel, y compris celles déjà couvertes par une
     * addition précédente déjà payée (double-facturation). Chaque commande
     * est maintenant explicitement rattachée à UNE addition précise
     * (commandes.addition_id) dès qu'elle y est intégrée, et n'est plus
     * jamais reprise dans le calcul d'une addition suivante.
     *
     * La remise des promotions est calculée ICI, pour l'ADDITION ENTIÈRE
     * (toutes les commandes qui lui sont rattachées), jamais commande par
     * commande — sinon une promo "montant fixe" serait déduite plusieurs
     * fois si la visite contient plusieurs commandes.
     */
    public function recalculerAddition(string $visiteId): void
    {
        $addition = Addition::where('visite_id', $visiteId)->where('statut', StatutAddition::OUVERTE)->first();
        $nouvelleAddition = !$addition;

        if (!$addition) {
            $addition = Addition::create([
                'visite_id' => $visiteId, 'sous_total' => 0, 'remise' => 0,
                'taxe' => 0, 'total' => 0, 'statut' => StatutAddition::OUVERTE,
            ]);
        }

        Commande::withoutGlobalScope(RestaurantScope::class)
            ->where('visite_id', $visiteId)
            ->where('statut', '!=', StatutCommande::ANNULEE)
            ->whereNull('addition_id')
            ->update(['addition_id' => $addition->id]);

        $commandes = Commande::withoutGlobalScope(RestaurantScope::class)
            ->where('addition_id', $addition->id)
            ->where('statut', '!=', StatutCommande::ANNULEE)
            ->with('lignes')->get();

        $sousTotal = $commandes->flatMap->lignes->sum('sous_total');
        $fraisLivraison = $commandes->sum('frais_livraison');

        $lignesPourPromo = $commandes->flatMap->lignes->map(fn ($l) => [
            'produit_id' => $l->produit_id, 'quantite' => $l->quantite, 'sous_total' => (float) $l->sous_total,
        ])->all();

        $restaurantId = $commandes->first()?->restaurant_id;
        $remise = $restaurantId ? $this->calculerRemisePromotions($restaurantId, $sousTotal, $lignesPourPromo) : 0;
        $total = $sousTotal + $fraisLivraison - $remise;

        $addition->update(['sous_total' => $sousTotal, 'remise' => $remise, 'total' => $total]);

        // Une seule fois par visite (à la création de l'addition), pas à chaque commande.
        if ($nouvelleAddition && $remise > 0 && $restaurantId) {
            \App\Models\Promotion::withoutGlobalScope(RestaurantScope::class)
                ->where('restaurant_id', $restaurantId)
                ->where('est_active', true)
                ->increment('nombre_utilisations');
        }
    }
   
}