<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        $admin = AdminUtilisateur::where('email', $data['email'])->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            throw ValidationException::withMessages(['email' => ['Email ou mot de passe incorrect.']]);
        }
        if (! $admin->actif) {
            throw ValidationException::withMessages(['email' => ['Ce compte admin est désactivé.']]);
        }

        // ⚠️ Expiration volontairement plus stricte que côté staff restaurant
        // (12h) : un compte Admin contrôle toute la plateforme, pas un seul
        // restaurant — le risque en cas de token oublié/volé est bien plus large.
        $token = $admin->createToken('admin-auth', ['*'], now()->addHours(12));

        return response()->json([
            'token' => $token->plainTextToken,
            'admin' => ['id' => $admin->id, 'nom_complet' => $admin->nom_complet, 'email' => $admin->email],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté.']);
    }
}