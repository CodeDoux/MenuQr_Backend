<?php

namespace App\Enums;

enum StatutAbonnement: string
{
    case ESSAI = 'ESSAI';
    case ACTIF = 'ACTIF';
    case EXPIRE = 'EXPIRE';
    case SUSPENDU = 'SUSPENDU';
    case ANNULE = 'ANNULE';
}