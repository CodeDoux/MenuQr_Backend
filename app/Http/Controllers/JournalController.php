<?php

namespace App\Http\Controllers;

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
            JournalActivite::with('utilisateur')
                ->latest('date')
                ->paginate(request()->integer('per_page', 30))
        );
    }
}