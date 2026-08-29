<?php

namespace App\Enums;

enum StatutTable: string
{
    case LIBRE = 'LIBRE';
    case OCCUPEE = 'OCCUPEE';
    case HORS_SERVICE = 'HORS_SERVICE';
}
