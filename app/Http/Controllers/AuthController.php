<?php

namespace App\Http\Controllers;

use App\Enums\StatutAbonnement;
use App\Enums\StatutAcces;
use App\Enums\StatutUtilisateur;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SelectRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Http\Resources\UserResource;
use App\Models\Abonnement;
use App\Models\Offre;
use App\Models\Restaurant;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription libre-service (décision actée : essai gratuit automatique,
     * aucun frais). Crée le Restaurant, l'Utilisateur, son accès Propriétaire
     * (déjà actif — pas d'invitation à s'accepter soi-même), et l'Abonnement
     * en période d'essai sur l'offre choisie.
     */
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $result = DB::transaction(function () use ($data) {
            $restaurant = Restaurant::create([
                'nom' => $data['restaurant_nom'],
                'adresse' => $data['restaurant_adresse'],
                'telephone' => $data['restaurant_telephone'],
                'statut' => 'ACTIF',
            ]);

            $user = User::create([
                'nom_complet' => $data['nom_complet'],
                'email' => $data['email'],
                'password' => $data['password'], // caché "hashed" sur le modèle
                'statut' => 'ACTIF',
            ]);

            $rolePropio = Role::where('code', 'PROPRIETAIRE')->firstOrFail();

            $acces = RestaurantUtilisateur::create([
                'restaurant_id' => $restaurant->id,
                'utilisateur_id' => $user->id,
                'role_id' => $rolePropio->id,
                'statut' => StatutAcces::ACTIF,
                'date_acceptation' => now(),
            ]);

            $offre = Offre::findOrFail($data['offre_id']);
            $dureeEssai = $offre->duree_essai ?? 14;

            Abonnement::create([
                'restaurant_id' => $restaurant->id,
                'offre_id' => $offre->id,
                'date_debut' => now(),
                'date_fin' => now()->addDays($dureeEssai),
                'statut' => StatutAbonnement::ESSAI,
                'renouvellement_automatique' => false,
            ]);

            // Amorçage : 7 jours (à configurer par le restaurant) + les 5 moyens
            // de paiement possibles (désactivés par défaut, à activer un à un).
            foreach (['LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE'] as $jour) {
                \App\Models\Horaire::create([
                    'restaurant_id' => $restaurant->id, 'jour_semaine' => $jour,
                    'heure_ouverture' => null, 'heure_fermeture' => null, 'est_ferme' => false,
                ]);
            }
            foreach (['ESPECES', 'WAVE', 'ORANGE_MONEY', 'CARTE', 'AUTRE'] as $methode) {
                \App\Models\MoyenPaiement::create([
                    'restaurant_id' => $restaurant->id, 'methode' => $methode, 'est_actif' => false,
                ]);
            }

            $token = $user->createToken('auth');
            $token->accessToken->forceFill(['restaurant_id' => $restaurant->id])->save();

            return [$user, $restaurant, $token->plainTextToken];
        });

        [$user, $restaurant, $plainTextToken] = $result;

        return response()->json([
            'token' => $plainTextToken,
            'user' => new UserResource($user),
            'restaurant' => new RestaurantResource($restaurant),
        ], 201);
    }

    /**
     * Connexion. Si l'utilisateur a plusieurs restaurants actifs, renvoie un
     * token pré-auth temporaire (aucun restaurant_id, capacité limitée à
     * select-restaurant) plutôt qu'un token final déjà scopé.
     */
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        if ($user->statut !== StatutUtilisateur::ACTIF) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte est désactivé ou bloqué.'],
            ]);
        }

        $accesActifs = RestaurantUtilisateur::with(['restaurant', 'role'])
            ->where('utilisateur_id', $user->id)
            ->where('statut', StatutAcces::ACTIF)
            ->get();

        if ($accesActifs->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => ['Aucun accès actif à un restaurant pour ce compte.'],
            ]);
        }

        if ($accesActifs->count() === 1) {
            $acces = $accesActifs->first();
            $token = $user->createToken('auth');
            $token->accessToken->forceFill(['restaurant_id' => $acces->restaurant_id])->save();

            return response()->json([
                'token' => $token->plainTextToken,
                'user' => new UserResource($user),
                'restaurant' => new RestaurantResource($acces->restaurant),
            ]);
        }

        // Plusieurs restaurants : token pré-auth de courte durée, sans restaurant_id.
        $preAuthToken = $user->createToken('pre-auth', ['select-restaurant'], now()->addMinutes(5));

        return response()->json([
            'pre_auth_token' => $preAuthToken->plainTextToken,
            'restaurants' => RestaurantResource::collection(
                $accesActifs->pluck('restaurant')
            ),
        ]);
    }

    /**
     * Deuxième étape du login multi-restaurant : échange le token pré-auth
     * contre un token final scopé au restaurant choisi.
     */
    public function selectRestaurant(SelectRestaurantRequest $request)
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        if (! $currentToken->can('select-restaurant') || $currentToken->restaurant_id !== null) {
            return response()->json([
                'message' => 'Token invalide pour cette opération.',
                'code' => 'INVALID_TOKEN_CONTEXT',
            ], 403);
        }

        $acces = RestaurantUtilisateur::where('utilisateur_id', $user->id)
            ->where('restaurant_id', $request->validated('restaurant_id'))
            ->where('statut', StatutAcces::ACTIF)
            ->with('restaurant')
            ->first();

        if (! $acces) {
            return response()->json([
                'message' => 'Accès non autorisé à ce restaurant.',
                'code' => 'FORBIDDEN_RESTAURANT',
            ], 403);
        }

        $currentToken->delete();

        $token = $user->createToken('auth');
        $token->accessToken->forceFill(['restaurant_id' => $acces->restaurant_id])->save();

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
            'restaurant' => new RestaurantResource($acces->restaurant),
        ]);
    }

    public function logout(\Illuminate\Http\Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }
}
