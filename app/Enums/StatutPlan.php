<?php

namespace App\Enums;

enum StatutPlan: string
{
    case ACTIF = 'ACTIF';
    case INACTIF = 'INACTIF';
    case ARCHIVE = 'ARCHIVE';
}