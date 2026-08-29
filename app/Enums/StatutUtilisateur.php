<?php

namespace App\Enums;

enum StatutUtilisateur: string
{
    case ACTIF = 'ACTIF';
    case INACTIF = 'INACTIF';
    case BLOQUE = 'BLOQUE';
    case SUPPRIME = 'SUPPRIME';
}