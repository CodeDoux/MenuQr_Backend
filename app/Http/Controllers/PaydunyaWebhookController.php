<?php

namespace App\Http\Controllers;

use App\Models\Addition;
use App\Models\Facture;
use App\Models\Paiement;
use App\Services\PaydunyaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ⚠️ IPN publique (appelée par PayDunya). On ne fait JAMAIS confiance au
 * contenu du corps reçu — on récupère uniquement le token, puis on
 * revérifie nous-mêmes le VRAI statut auprès de PayDunya avant de créditer
 * quoi que ce soit. Gère les DEUX types de paiement (ABONNEMENT et
 * COMMANDE) — distingués via Paiement::type, retrouvé par le token commun.
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

        $paiement = Paiement::where('reference', $token)->first();

        if (!$paiement) {
            Log::warning('Webhook PayDunya : aucun paiement local ne correspond à ce token.', ['token' => $token]);
            return response()->json(['message' => 'OK']);
        }

        // Idempotent : si déjà confirmé, ne refait rien.
        if ($paiement->statut === 'CONFIRME') {
            return response()->json(['message' => 'OK']);
        }

        $statutReel = $paydunya->verifierStatut($token);

        if ($statutReel === 'completed') {
            DB::transaction(function () use ($paiement) {
                $paiement->update(['statut' => 'CONFIRME', 'date_paiement' => now()]);

                if ($paiement->type === 'ABONNEMENT') {
                    $this->confirmerPaiementAbonnement($paiement);
                } else {
                    $this->confirmerPaiementCommande($paiement);
                }
            });
        } else {
            $paiement->update(['statut' => 'ECHOUE']);
        }

        return response()->json(['message' => 'OK']);
    }

    private function confirmerPaiementAbonnement($paiement): void
    {
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
    }

    private function confirmerPaiementCommande($paiement): void
    {
        if ($paiement->addition_id) {
            $addition = Addition::find($paiement->addition_id);
            $addition->update(['statut' => 'PAYEE']);
            Facture::create([
                'numero' => 'FAC-'.now()->format('YmdHis'), 'addition_id' => $addition->id,
                'montant_ht' => $addition->total, 'taxe' => 0, 'montant_ttc' => $addition->total,
                'date_emission' => now(), 'statut' => 'PAYEE',
            ]);
        } elseif ($paiement->commande_id) {
            Facture::create([
                'numero' => 'FAC-'.now()->format('YmdHis'), 'commande_id' => $paiement->commande_id,
                'montant_ht' => $paiement->montant, 'taxe' => 0, 'montant_ttc' => $paiement->montant,
                'date_emission' => now(), 'statut' => 'PAYEE',
            ]);
        }
    }
}