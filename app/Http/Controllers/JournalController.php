<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JournalActiviteResource;
use App\Models\JournalActivite;
use Illuminate\Support\Facades\Gate;

class JournalController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', JournalActivite::class);
        return JournalActiviteResource::collection(
            JournalActivite::with('utilisateur')->latest('date')->limit(200)->get()
        );
    }
}
