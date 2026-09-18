<?php

namespace App\Http\Controllers;

use App\Enums\StatutAcces;
use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantResource;
use App\Http\Resources\UserResource;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Services\AdminJournalService;
use Illuminate\Http\Request;

/**
 * ⚠️ Action sensible et systématiquement journalisée : un Admin peut se
 * connecter TEMPORAIREMENT à la place d'un restaurant pour l'assister
 * (support client), jamais silencieusement. Le token généré est limité en
 * durée (1h) et distinct des tokens normaux (nom "impersonation" +
 * enregistrement explicite de qui l'a initié).
 */
class AdminImpersonationController extends Controller
{
    public function __construct(private readonly AdminJournalService $journal) {}

    public function impersonate(Request $request, string $restaurantId)
    {
        $admin = $request->user();
        $restaurant = Restaurant::findOrFail($restaurantId);

        $acces = RestaurantUtilisateur::with(['utilisateur', 'role.permissions'])
            ->where('restaurant_id', $restaurantId)
            ->where('statut', StatutAcces::ACTIF)
            ->whereHas('role', fn ($q) => $q->where('code', 'PROPRIETAIRE'))
            ->first();

        if (! $acces) {
            return response()->json(['message' => 'Aucun Propriétaire actif trouvé pour ce restaurant.'], 404);
        }

        $token = $acces->utilisateur->createToken('impersonation', ['*'], now()->addHour());
        $token->accessToken->forceFill(['restaurant_id' => $restaurantId])->save();

        $this->journal->enregistrer(
            $admin->id,
            'impersonation',
            'restaurants',
            $restaurantId,
            null,
            ['restaurant_nom' => $restaurant->nom, 'utilisateur_cible' => $acces->utilisateur->email]
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($acces->utilisateur),
            'restaurant' => new RestaurantResource($restaurant),
            'role' => $acces->role->code,
            'permissions' => $acces->role->permissions->pluck('code'),
        ]);
    }
}