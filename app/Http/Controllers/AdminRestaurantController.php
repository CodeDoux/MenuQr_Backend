<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminRestaurantResource;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class AdminRestaurantController extends Controller
{
    public function index()
    {
        return AdminRestaurantResource::collection(Restaurant::orderBy('nom')->get());
    }

    public function changerStatut(Request $request, string $id)
    {
        $data = $request->validate(['statut' => ['required', 'in:ACTIF,SUSPENDU,INACTIF,FERME']]);

        $restaurant = Restaurant::findOrFail($id);
        $restaurant->update(['statut' => $data['statut']]);

        return new AdminRestaurantResource($restaurant);
    }
}