<?php

namespace App\Enums;

enum StatutRestaurant: string
{
    case ACTIF = 'ACTIF';
    case SUSPENDU = 'SUSPENDU';
    case INACTIF = 'INACTIF';
    case FERME = 'FERME';
}