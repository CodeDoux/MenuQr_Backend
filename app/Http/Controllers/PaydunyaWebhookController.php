<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Services\PaydunyaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ⚠️ IPN publique (appelée par PayDunya, jamais par notre frontend). On ne
 * fait JAMAIS confiance au contenu du corps reçu (spoofable) — on récupère
 * uniquement le token, puis on revérifie nous-mêmes le VRAI statut auprès
 * de PayDunya (serveur à serveur, avec nos clés privées) avant de créditer
 * quoi que ce soit. Toujours répondre 200 à PayDunya, même en cas d'échec
 * métier, sinon PayDunya reste bloqué à réessayer indéfiniment.
 */
class PaydunyaWebhookController extends Controller
{
    public function handle(Request $request, PaydunyaService $paydunya)
    {
        $token = $request->input('data.invoice.token') ?? $request->input('token');

        if (!$token) {
            Log::warning('Webhook PayDunya reçu sans token.', $request->all());
            return response()->json(['message' => 'Token manquant.']);
        }

        $paiement = Paiement::where('type', 'ABONNEMENT')->where('reference', $token)->first();

        if (!$paiement) {
            Log::warning('Webhook PayDunya : aucun paiement local ne correspond à ce token.', ['token' => $token]);
            return response()->json(['message' => 'OK']);
        }

        // Idempotent : si déjà confirmé, ne refait rien (PayDunya peut renvoyer plusieurs fois le même IPN).
        if ($paiement->statut === 'CONFIRME') {
            return response()->json(['message' => 'OK']);
        }

        $statutReel = $paydunya->verifierStatut($token);

        if ($statutReel === 'completed') {
            DB::transaction(function () use ($paiement) {
                $paiement->update(['statut' => 'CONFIRME', 'date_paiement' => now()]);

                $facture = $paiement->factureAbonnement;
                $facture->update(['statut' => 'PAYEE']);

                $abonnement = $facture->abonnement;
                $baseDate = ($abonnement->date_fin && $abonnement->date_fin->isFuture())
                    ? $abonnement->date_fin->copy()
                    : now();

                $abonnement->update([
                    'statut' => 'ACTIF',
                    'date_fin' => $baseDate->addDays(30),
                ]);
            });
        } else {
            $paiement->update(['statut' => 'ECHOUE']);
        }

        return response()->json(['message' => 'OK']);
    }
}