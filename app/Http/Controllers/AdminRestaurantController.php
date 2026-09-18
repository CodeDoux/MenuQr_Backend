<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminRestaurantResource;
use App\Models\Restaurant;
use App\Services\AdminJournalService;
use Illuminate\Http\Request;

class AdminRestaurantController extends Controller
{
    public function __construct(private readonly AdminJournalService $journal) {}

    public function index()
    {
        return AdminRestaurantResource::collection(Restaurant::orderBy('nom')->get());
    }

    public function changerStatut(Request $request, string $id)
    {
        $data = $request->validate(['statut' => ['required', 'in:ACTIF,SUSPENDU,INACTIF,FERME']]);

        $restaurant = Restaurant::findOrFail($id);
        $ancienStatut = $restaurant->statut;

        $restaurant->update(['statut' => $data['statut']]);

        $this->journal->enregistrer(
            $request->user()->id,
            'changement_statut_restaurant',
            'restaurants',
            $id,
            ['statut' => $ancienStatut, 'nom' => $restaurant->nom],
            ['statut' => $data['statut']]
        );

        return new AdminRestaurantResource($restaurant);
    }
}