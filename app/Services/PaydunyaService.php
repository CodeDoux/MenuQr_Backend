<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ⚠️ La confirmation d'un paiement ne doit JAMAIS se fier uniquement au
 * contenu du webhook reçu (spoofable) — on rappelle toujours l'API de
 * PayDunya nous-mêmes (serveur à serveur, avec nos clés privées) pour
 * obtenir le VRAI statut avant de créditer quoi que ce soit.
 */
class PaydunyaService
{
    private string $baseUrl;

    public function __construct()
    {
        $mode = config('services.paydunya.mode', 'sandbox');
        $this->baseUrl = $mode === 'live'
            ? 'https://app.paydunya.com/api/v1'
            : 'https://app.paydunya.com/sandbox-api/v1';
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'PAYDUNYA-MASTER-KEY' => config('services.paydunya.master_key'),
            'PAYDUNYA-PRIVATE-KEY' => config('services.paydunya.private_key'),
            'PAYDUNYA-TOKEN' => config('services.paydunya.token'),
        ];
    }

    public function creerFacture(float $montant, string $description, string $returnUrl, string $callbackUrl): array
    {
        $reponse = Http::withHeaders($this->headers())->post("{$this->baseUrl}/checkout-invoice/create", [
            'invoice' => [
                'total_amount' => (int) round($montant),
                'description' => $description,
            ],
            'store' => [
                'name' => 'MenuQr',
            ],
            'actions' => [
                'return_url' => $returnUrl,
                'callback_url' => $callbackUrl,
            ],
        ]);

        $donnees = $reponse->json();

        if (! $reponse->successful() || ($donnees['response_code'] ?? null) !== '00') {
            Log::error('Échec de création de facture PayDunya', ['reponse' => $donnees]);
            throw new \RuntimeException($donnees['response_text'] ?? 'Erreur PayDunya inconnue.');
        }

        return [
            'token' => $donnees['token'],
            'url' => $donnees['response_text'],
        ];
    }

    /**
     * Vérifie le VRAI statut d'une facture directement auprès de PayDunya
     * (jamais depuis les données du webhook seules). Renvoie 'completed',
     * 'pending', 'cancelled' ou 'failed'.
     */
    public function verifierStatut(string $token): string
    {
        $reponse = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/checkout-invoice/confirm/{$token}");

        $donnees = $reponse->json();

        return $donnees['status'] ?? 'failed';
    }
}