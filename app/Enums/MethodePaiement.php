<?php
namespace App\Enums;
enum MethodePaiement: string {
    case ESPECES = 'ESPECES'; case WAVE = 'WAVE'; case ORANGE_MONEY = 'ORANGE_MONEY';
    case CARTE = 'CARTE'; case AUTRE = 'AUTRE';
}