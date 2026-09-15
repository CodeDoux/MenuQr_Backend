<?php

namespace App\Enums;

enum TypeNotification: string
{
    case NOUVELLE_COMMANDE = 'NOUVELLE_COMMANDE';
    case STOCK_RUPTURE = 'STOCK_RUPTURE';
    case ABONNEMENT_EXPIRE_BIENTOT = 'ABONNEMENT_EXPIRE_BIENTOT';
}