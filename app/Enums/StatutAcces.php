<?php

namespace App\Enums;

enum StatutAcces: string
{
    case INVITE = 'INVITE';
    case ACTIF = 'ACTIF';
    case SUSPENDU = 'SUSPENDU';
    case REVOQUE = 'REVOQUE';
}