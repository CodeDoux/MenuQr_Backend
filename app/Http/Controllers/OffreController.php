<?php

namespace App\Http\Controllers;

use App\Enums\StatutPlan;
use App\Http\Controllers\Controller;
use App\Http\Resources\OffreResource;
use App\Models\Offre;

class OffreController extends Controller
{
    /** Liste publique — consultée avant inscription, pas d'authentification requise. */
    public function index()
    {
        $offres = Offre::where('statut', StatutPlan::ACTIF)
            ->orderBy('ordre_affichage')
            ->get();

        return OffreResource::collection($offres);
    }
}
