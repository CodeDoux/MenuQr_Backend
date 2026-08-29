<?php
namespace App\Enums;
enum StatutCommande: string {
    case EN_ATTENTE = 'EN_ATTENTE';
    case CONFIRMEE = 'CONFIRMEE';
    case EN_PREPARATION = 'EN_PREPARATION';
    case PRETE = 'PRETE';
    case SERVIE = 'SERVIE';
    case REMISE = 'REMISE';
    case LIVREE = 'LIVREE';
    case ANNULEE = 'ANNULEE';
}