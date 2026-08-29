<?php

namespace App\Http\Middleware;

use App\Models\AdminUtilisateur;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof AdminUtilisateur) {
            return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);
        }

        if (! $user->actif) {
            $user->currentAccessToken()->delete();
            return response()->json(['message' => 'Ce compte admin a été désactivé.'], 403);
        }

        return $next($request);
    }
}