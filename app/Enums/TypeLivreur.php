<?php
namespace App\Enums;
enum TypeLivreur: string {
    case EMPLOYE_RESTAURANT = 'EMPLOYE_RESTAURANT';
    case PRESTATAIRE_EXTERNE = 'PRESTATAIRE_EXTERNE';
    case LIVREUR_CLIENT = 'LIVREUR_CLIENT';
}