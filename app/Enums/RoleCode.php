<?php

namespace App\Enums;

enum RoleCode: string
{
    case PROPRIETAIRE = 'PROPRIETAIRE';
    case GERANT = 'GERANT';
    case SERVEUR = 'SERVEUR';
    case CUISINIER = 'CUISINIER';
    case CAISSIER = 'CAISSIER';
    case LIVREUR = 'LIVREUR';
}