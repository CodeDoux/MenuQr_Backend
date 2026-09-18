<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\JournalAdminResource;
use App\Models\JournalAdmin;

class JournalAdminController extends Controller
{
    public function index()
    {
        return JournalAdminResource::collection(
            JournalAdmin::with('admin')->latest('date')->paginate(request()->integer('per_page', 30))
        );
    }
}