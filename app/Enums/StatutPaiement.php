<?php
namespace App\Enums;
enum StatutPaiement: string {
    case EN_ATTENTE = 'EN_ATTENTE';
    case CONFIRME = 'CONFIRME';
    case ECHOUE = 'ECHOUE';
    case REMBOURSE = 'REMBOURSE';
    case ANNULE = 'ANNULE';
}
