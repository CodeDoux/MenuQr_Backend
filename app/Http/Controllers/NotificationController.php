<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * ⚠️ Pas de restaurant_id sur Notification — la sécurité vient du filtre
     * direct par utilisateur_id (l'utilisateur ne voit que SES notifications,
     * peu importe le restaurant). Pas besoin de RestaurantScope ici.
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('utilisateur_id', $request->user()->id)
            ->orderByDesc('date_envoie')
            ->limit(50)
            ->get();

        return NotificationResource::collection($notifications);
    }

    public function marquerLue(Request $request, string $id)
    {
        $notification = Notification::where('utilisateur_id', $request->user()->id)->findOrFail($id);
        $notification->update(['est_lu' => true]);

        return new NotificationResource($notification);
    }

    public function toutMarquerLu(Request $request)
    {
        Notification::where('utilisateur_id', $request->user()->id)
            ->where('est_lu', false)
            ->update(['est_lu' => true]);

        return response()->json(['message' => 'Notifications marquées comme lues.']);
    }
}