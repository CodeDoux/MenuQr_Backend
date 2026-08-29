<?php

namespace App\Http\Middleware;

use App\Enums\StatutAcces;
use App\Enums\StatutUtilisateur;
use App\Models\RestaurantUtilisateur;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout le restaurant actif à partir du token Sanctum (jamais d'un id
 * envoyé par le frontend), revérifie à CHAQUE requête que l'accès est
 * toujours actif (ferme la question de la révocation immédiate : un accès
 * suspendu/révoqué est coupé dès l'appel suivant, sans attendre l'expiration
 * du token), et alimente TenantContext pour le reste de la requête.
 */
class EnsureRestaurantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user || ! $token || empty($token->restaurant_id)) {
            return response()->json([
                'message' => 'Aucun restaurant actif pour ce token.',
                'code' => 'NO_RESTAURANT_CONTEXT',
            ], 403);
        }

        if ($user->statut !== StatutUtilisateur::ACTIF) {
            $token->delete();

            return response()->json([
                'message' => 'Compte désactivé ou bloqué.',
                'code' => 'ACCOUNT_DISABLED',
            ], 403);
        }

        $acces = RestaurantUtilisateur::with('role')
            ->where('restaurant_id', $token->restaurant_id)
            ->where('utilisateur_id', $user->id)
            ->first();

        if (! $acces || $acces->statut !== StatutAcces::ACTIF) {
            $token->delete();

            return response()->json([
                'message' => 'Votre accès à ce restaurant a été révoqué ou suspendu.',
                'code' => 'ACCESS_REVOKED',
            ], 403);
        }

        app(TenantContext::class)->definir($acces->restaurant, $acces);

        return $next($request);
    }
}