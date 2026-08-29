<?php

namespace App\Enums;

enum StatutEmploye: string
{
    case ACTIF = 'ACTIF';
    case EN_CONGE = 'EN_CONGE';
    case SUSPENDU = 'SUSPENDU';
    case TERMINE = 'TERMINE';
}