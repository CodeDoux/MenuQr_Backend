<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUtilisateurRequest;
use App\Http\Resources\AdminUtilisateurResource;
use App\Models\AdminUtilisateur;
use Illuminate\Support\Str;

class AdminUtilisateurController extends Controller
{
    public function index()
    {
        return AdminUtilisateurResource::collection(AdminUtilisateur::orderBy('nom_complet')->get());
    }

    /** ⚠️ Mot de passe temporaire aléatoire — pas d'envoi d'email pour
     *  l'instant (hors scope). Le compte devra le réinitialiser. */
    public function store(AdminUtilisateurRequest $request)
    {
        $admin = AdminUtilisateur::create([
            ...$request->validated(),
            'password' => Str::random(24),
            'actif' => true,
        ]);

        return new AdminUtilisateurResource($admin);
    }

    public function toggleActif(string $id)
    {
        $admin = AdminUtilisateur::findOrFail($id);
        $admin->update(['actif' => ! $admin->actif]);
        return new AdminUtilisateurResource($admin);
    }

    public function destroy(string $id)
    {
        AdminUtilisateur::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}