<?php

namespace App\Http\Controllers;

use App\Enums\StatutAcces;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Models\Employe;
use App\Models\Scopes\RestaurantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * ⚠️ Routes PUBLIQUES (pas de auth:sanctum) : la personne invitée n'a pas
 * encore de mot de passe utilisable — elle ne peut donc pas s'authentifier
 * avant d'avoir accepté. Même logique que l'inscription (register).
 *
 * Employe utilise BelongsToRestaurant (RestaurantScope), qui exige un
 * TenantContext — inexistant ici puisque la route est publique. On
 * contourne explicitement le scope : l'UUID de l'employé dans l'URL fait
 * déjà office de jeton d'accès suffisamment peu devinable pour cette
 * opération de lecture/acceptation ponctuelle.
 */
class InvitationController extends Controller
{
    public function show(string $employeId)
    {
        $employe = Employe::withoutGlobalScope(RestaurantScope::class)
            ->with(['utilisateur', 'accesPlateforme.role', 'restaurant'])
            ->find($employeId);

        if (!$employe || !$employe->accesPlateforme || $employe->accesPlateforme->statut !== StatutAcces::INVITE) {
            return response()->json(['message' => 'Invitation invalide ou déjà utilisée.'], 404);
        }

        return response()->json([
            'nom_complet' => $employe->utilisateur->nom_complet,
            'role' => $employe->accesPlateforme->role->code,
            'restaurant_nom' => $employe->restaurant->nom,
        ]);
    }

    public function accepter(AcceptInvitationRequest $request, string $employeId)
    {
        $employe = Employe::withoutGlobalScope(RestaurantScope::class)
            ->with(['utilisateur', 'accesPlateforme.role.permissions', 'restaurant'])
            ->find($employeId);

        if (!$employe || !$employe->accesPlateforme || $employe->accesPlateforme->statut !== StatutAcces::INVITE) {
            return response()->json(['message' => 'Invitation invalide ou déjà utilisée.'], 404);
        }

        $token = DB::transaction(function () use ($employe, $request) {
            $employe->utilisateur->update(['password' => Hash::make($request->validated('password'))]);
            $employe->accesPlateforme->update(['statut' => StatutAcces::ACTIF, 'date_acceptation' => now()]);

            $token = $employe->utilisateur->createToken('auth');
            $token->accessToken->forceFill(['restaurant_id' => $employe->restaurant_id])->save();

            return $token->plainTextToken;
        });

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $employe->utilisateur->id,
                'nom_complet' => $employe->utilisateur->nom_complet,
                'email' => $employe->utilisateur->email,
            ],
            'restaurant' => ['id' => $employe->restaurant->id, 'nom' => $employe->restaurant->nom],
            'role' => $employe->accesPlateforme->role->code,
            'permissions' => $employe->accesPlateforme->role->permissions->pluck('code'),
        ]);
    }
}