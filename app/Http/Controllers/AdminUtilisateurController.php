<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUtilisateurRequest;
use App\Http\Resources\AdminUtilisateurResource;
use App\Models\AdminUtilisateur;
use App\Services\AdminJournalService;
use Illuminate\Support\Str;

class AdminUtilisateurController extends Controller
{
    public function __construct(private readonly AdminJournalService $journal) {}

    public function index()
    {
        return AdminUtilisateurResource::collection(AdminUtilisateur::orderBy('nom_complet')->get());
    }

    public function store(AdminUtilisateurRequest $request)
    {
        $admin = AdminUtilisateur::create([
            ...$request->validated(),
            'password' => Str::random(24),
            'actif' => true,
        ]);

        $this->journal->enregistrer(
            $request->user()->id,
            'creation_admin',
            'admin_utilisateurs',
            $admin->id,
            null,
            ['nom_complet' => $admin->nom_complet, 'email' => $admin->email]
        );

        return new AdminUtilisateurResource($admin);
    }

    public function toggleActif(string $id)
    {
        $admin = AdminUtilisateur::findOrFail($id);
        $ancienEtat = $admin->actif;

        // Garde-fou : jamais désactiver le DERNIER admin actif restant —
        // ça bloquerait totalement l'accès à la plateforme.
        if ($ancienEtat) {
            $autresActifs = AdminUtilisateur::where('actif', true)->where('id', '!=', $id)->count();
            if ($autresActifs === 0) {
                return response()->json(['message' => 'Impossible de désactiver le dernier administrateur actif.'], 422);
            }
        }

        $admin->update(['actif' => ! $admin->actif]);

        $this->journal->enregistrer(
            request()->user()->id,
            'toggle_actif_admin',
            'admin_utilisateurs',
            $id,
            ['actif' => $ancienEtat],
            ['actif' => $admin->actif]
        );

        return new AdminUtilisateurResource($admin);
    }

    public function destroy(string $id)
    {
        $admin = AdminUtilisateur::findOrFail($id);

        // Même garde-fou : jamais supprimer le DERNIER admin actif restant.
        if ($admin->actif) {
            $autresActifs = AdminUtilisateur::where('actif', true)->where('id', '!=', $id)->count();
            if ($autresActifs === 0) {
                return response()->json(['message' => 'Impossible de supprimer le dernier administrateur actif.'], 422);
            }
        }

        $ancienneValeur = ['nom_complet' => $admin->nom_complet, 'email' => $admin->email];

        $admin->delete();

        $this->journal->enregistrer(
            request()->user()->id,
            'suppression_admin',
            'admin_utilisateurs',
            $id,
            $ancienneValeur,
            null
        );

        return response()->json(null, 204);
    }
}