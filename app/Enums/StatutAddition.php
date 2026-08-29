<?php
namespace App\Enums;
enum StatutAddition: string {
    case OUVERTE = 'OUVERTE';
    case PARTIELLEMENT_PAYEE = 'PARTIELLEMENT_PAYEE';
    case PAYEE = 'PAYEE';
    case ANNULEE = 'ANNULEE';
}
>