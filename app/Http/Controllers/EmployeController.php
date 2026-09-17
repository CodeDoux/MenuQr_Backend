<?php

namespace App\Http\Controllers;

use App\Enums\StatutAcces;
use App\Enums\StatutEmploye;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeRequest;
use App\Http\Resources\EmployeResource;
use App\Mail\InvitationEmployeMail;
use App\Models\Employe;
use App\Models\RestaurantUtilisateur;
use App\Models\Role;
use App\Services\JournalService;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class EmployeController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly JournalService $journal
    ) {}

    public function index()
    {
        if (! $this->tenant->aLaPermission('employe.gerer')) {
            abort(403);
        }

        $employes = Employe::with(['utilisateur', 'poste', 'accesPlateforme.role'])->get();

        return EmployeResource::collection($employes);
    }

    public function store(EmployeRequest $request)
    {
        Gate::authorize('create', Employe::class);

        $data = $request->validated();

        $employe = DB::transaction(function () use ($data) {
            $utilisateur = User::firstOrCreate(
                ['email' => $data['email']],
                ['nom_complet' => $data['nom_complet'], 'password' => null, 'statut' => 'ACTIF']
            );

            $accesId = null;
            $acces = null;
            $role = null;

            if ($data['accorder_acces']) {
                $role = Role::where('code', $data['role'])->firstOrFail();

                $acces = RestaurantUtilisateur::firstOrCreate(
                    ['restaurant_id' => $this->tenant->restaurantId, 'utilisateur_id' => $utilisateur->id],
                    ['role_id' => $role->id, 'statut' => StatutAcces::INVITE, 'date_invitation' => now()]
                );
                if ($acces->role_id !== $role->id) {
                    $acces->update(['role_id' => $role->id]);
                }
                $accesId = $acces->id;
            }

            $employe = Employe::create([
                'utilisateur_id' => $utilisateur->id,
                'poste_id' => $data['poste_id'],
                'restaurant_utilisateur_id' => $accesId,
                'matricule' => $data['matricule'] ?? null,
                'date_embauche' => $data['date_embauche'] ?? null,
                'statut' => $data['statut'],
                'notes' => $data['notes'] ?? null,
            ]);

            // N'envoie l'email que si l'accès est réellement en attente
            // d'acceptation (pas si la personne a déjà un compte actif ailleurs).
            if ($acces && $acces->statut === StatutAcces::INVITE) {
                $this->envoyerInvitation($employe, $utilisateur, $role);
            }

            return $employe;
        });

        $this->journal->enregistrer('creation_employe', 'employes', $employe->id, null, ['nom' => $data['nom_complet']]);

        return new EmployeResource($employe->load(['utilisateur', 'poste', 'accesPlateforme.role']));
    }

    public function update(EmployeRequest $request, string $employe)
    {
        $employeModel = Employe::with('accesPlateforme')->findOrFail($employe);
        Gate::authorize('update', $employeModel);

        $data = $request->validated();

        DB::transaction(function () use ($employeModel, $data) {
            $employeModel->utilisateur()->update([
                'nom_complet' => $data['nom_complet'],
                'email' => $data['email'],
            ]);

            $accesId = $employeModel->restaurant_utilisateur_id;

            if ($data['accorder_acces']) {
                $role = Role::where('code', $data['role'])->firstOrFail();

                if ($employeModel->accesPlateforme) {
                    $employeModel->accesPlateforme->update(['role_id' => $role->id]);
                } else {
                    $acces = RestaurantUtilisateur::create([
                        'restaurant_id' => $this->tenant->restaurantId,
                        'utilisateur_id' => $employeModel->utilisateur_id,
                        'role_id' => $role->id,
                        'statut' => StatutAcces::INVITE,
                        'date_invitation' => now(),
                    ]);
                    $accesId = $acces->id;

                    // Nouvel accès créé depuis Modifier : c'est aussi une invitation.
                    $this->envoyerInvitation($employeModel, $employeModel->utilisateur, $role);
                }
            } elseif ($employeModel->accesPlateforme) {
                $employeModel->accesPlateforme->update(['statut' => StatutAcces::REVOQUE]);
            }

            $employeModel->update([
                'poste_id' => $data['poste_id'],
                'restaurant_utilisateur_id' => $accesId,
                'matricule' => $data['matricule'] ?? null,
                'date_embauche' => $data['date_embauche'] ?? null,
                'statut' => $data['statut'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return new EmployeResource($employeModel->fresh(['utilisateur', 'poste', 'accesPlateforme.role']));
    }

    /** Fin de contrat — pas de suppression physique (cohérence avec l'historique des commandes/actions). */
    public function terminer(string $employe)
    {
        $employeModel = Employe::with('accesPlateforme')->findOrFail($employe);
        Gate::authorize('delete', $employeModel);

        DB::transaction(function () use ($employeModel) {
            $employeModel->update(['statut' => StatutEmploye::TERMINE, 'date_fin' => now()]);
            $employeModel->accesPlateforme?->update(['statut' => StatutAcces::REVOQUE]);
        });

        $this->journal->enregistrer('fin_contrat_employe', 'employes', $employeModel->id, null, null);
        return response()->json(null, 204);
    }

    public function renvoyerInvitation(string $employe)
    {
        $employeModel = Employe::with(['accesPlateforme.role', 'utilisateur'])->findOrFail($employe);
        Gate::authorize('update', $employeModel);

        if (!$employeModel->accesPlateforme || $employeModel->accesPlateforme->statut !== StatutAcces::INVITE) {
            return response()->json(['message' => 'Aucune invitation en attente pour cet employé.'], 422);
        }

        $employeModel->accesPlateforme->update(['date_invitation' => now()]);

        $this->envoyerInvitation($employeModel, $employeModel->utilisateur, $employeModel->accesPlateforme->role);

        return response()->json(['message' => 'Invitation renvoyée par email.']);
    }

    private function envoyerInvitation(Employe $employe, User $utilisateur, Role $role): void
    {
        $lien = config('app.frontend_url')."/invitations/{$employe->id}";

        Mail::to($utilisateur->email)->send(new InvitationEmployeMail(
            $utilisateur->nom_complet,
            $this->tenant->restaurant->nom,
            $role->nom,
            $lien
        ));
    }
}