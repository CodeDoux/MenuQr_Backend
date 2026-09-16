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
     * aucun frais, AUCUN BLOCAGE le temps de vérifier l'email — cohérent
     * avec la philosophie "zéro friction" déjà actée pour ce flux). La
     * vérification d'email est donc un simple rappel affiché côté frontend
     * tant que email_verifie_le est null, jamais un blocage de connexion.
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

            $urlVerification = $this->genererLienVerification($user->email);

            $acces->load('role.permissions');

            return [$user, $restaurant, $token->plainTextToken, $acces->role, $urlVerification];
        });

        [$user, $restaurant, $plainTextToken, $role, $urlVerification] = $result;

        return response()->json([
            'token' => $plainTextToken,
            'user' => new UserResource($user),
            'restaurant' => new RestaurantResource($restaurant),
            'role' => $role->code,
            'permissions' => $role->permissions->pluck('code'),
            'verification_url' => $urlVerification,
        ], 201);
    }

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
            $acces->load('role.permissions');
            $token = $user->createToken('auth');
            $token->accessToken->forceFill(['restaurant_id' => $acces->restaurant_id])->save();

            return response()->json([
                'token' => $token->plainTextToken,
                'user' => new UserResource($user),
                'restaurant' => new RestaurantResource($acces->restaurant),
                'role' => $acces->role->code,
                'permissions' => $acces->role->permissions->pluck('code'),
            ]);
        }

        $preAuthToken = $user->createToken('pre-auth', ['select-restaurant'], now()->addMinutes(5));

        return response()->json([
            'pre_auth_token' => $preAuthToken->plainTextToken,
            'restaurants' => RestaurantResource::collection(
                $accesActifs->pluck('restaurant')
            ),
        ]);
    }

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
            ->with(['restaurant', 'role.permissions'])
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
            'role' => $acces->role->code,
            'permissions' => $acces->role->permissions->pluck('code'),
        ]);
    }

    public function logout(\Illuminate\Http\Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function changerMotDePasse(\App\Http\Requests\ChangerMotDePasseRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->validated('mot_de_passe_actuel'), $user->password)) {
            throw ValidationException::withMessages([
                'mot_de_passe_actuel' => ['Mot de passe actuel incorrect.'],
            ]);
        }

        $user->update(['password' => $request->validated('nouveau_mot_de_passe')]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    public function modifierProfil(\App\Http\Requests\ModifierProfilRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    public function motDePasseOublie(\App\Http\Requests\MotDePasseOublieRequest $request)
    {
        $email = $request->validated('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json(['message' => 'Si un compte existe avec cet email, un lien a été généré.']);
        }

        $token = \Illuminate\Support\Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $lienReinitialisation = config('app.frontend_url').'/reinitialisation?email='.urlencode($email).'&token='.$token;

        return response()->json([
            'message' => 'Si un compte existe avec cet email, un lien a été généré.',
            'reset_url' => $lienReinitialisation,
        ]);
    }

    public function reinitialiserMotDePasse(\App\Http\Requests\ReinitialiserMotDePasseRequest $request)
    {
        $data = $request->validated();

        $enregistrement = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $enregistrement || ! Hash::check($data['token'], $enregistrement->token)) {
            throw ValidationException::withMessages(['token' => ['Lien invalide ou déjà utilisé.']]);
        }

        if (now()->diffInMinutes($enregistrement->created_at) > 60) {
            throw ValidationException::withMessages(['token' => ['Ce lien a expiré. Demandez-en un nouveau.']]);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->update(['password' => $data['nouveau_mot_de_passe']]);

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }

    /**
     * ⚠️ Même approche que le reste (pas d'email réel envoyé) : le lien est
     * renvoyé dans la réponse. NE BLOQUE JAMAIS la connexion (décision
     * actée) — sert uniquement à afficher un bandeau de rappel côté frontend
     * tant que email_verifie_le est null.
     */
    public function verifierEmail(\Illuminate\Http\Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'token' => ['required', 'string']]);

        $enregistrement = DB::table('email_verification_tokens')->where('email', $data['email'])->first();

        if (! $enregistrement || ! Hash::check($data['token'], $enregistrement->token)) {
            throw ValidationException::withMessages(['token' => ['Lien invalide ou déjà utilisé.']]);
        }

        if (now()->diffInHours($enregistrement->created_at) > 24) {
            throw ValidationException::withMessages(['token' => ['Ce lien a expiré. Demandez-en un nouveau.']]);
        }

        User::where('email', $data['email'])->update(['email_verifie_le' => now()]);

        DB::table('email_verification_tokens')->where('email', $data['email'])->delete();

        return response()->json(['message' => 'Email vérifié avec succès.']);
    }

    /** Renvoie un nouveau lien — l'utilisateur doit être connecté (pas
     *  besoin de ressaisir son email, on utilise le sien directement). */
    public function renvoyerVerificationEmail(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        if ($user->email_verifie_le) {
            return response()->json(['message' => 'Cet email est déjà vérifié.']);
        }

        $url = $this->genererLienVerification($user->email);

        return response()->json(['message' => 'Lien généré.', 'verification_url' => $url]);
    }

    private function genererLienVerification(string $email): string
    {
        $token = \Illuminate\Support\Str::random(64);

        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        return config('app.frontend_url').'/verification-email?email='.urlencode($email).'&token='.$token;
    }
}