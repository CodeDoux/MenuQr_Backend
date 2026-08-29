<?php
namespace App\Enums;
enum StatutLivraison: string {
    case EN_ATTENTE_AFFECTATION = 'EN_ATTENTE_AFFECTATION';
    case AFFECTEE = 'AFFECTEE';
    case RECUPEREE = 'RECUPEREE';
    case EN_ROUTE = 'EN_ROUTE';
    case LIVREE = 'LIVREE';
    case ANNULEE = 'ANNULEE';
}
>