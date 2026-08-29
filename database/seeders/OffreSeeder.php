<?php

namespace Database\Seeders;

use App\Models\Offre;
use Illuminate\Database\Seeder;

/**
 * ⚠️ Offre de test minimale, à remplacer par de vraies offres commerciales
 * (via l'interface Admin MenuQR à construire, ou un seeder définitif une
 * fois les tarifs réels communiqués).
 */
class OffreSeeder extends Seeder
{
    public function run(): void
    {
        Offre::updateOrCreate(
            ['nom' => 'Test'],
            [
                'description' => 'Offre de test — à remplacer par de vraies offres commerciales.',
                'prix_mensuel' => 0,
                'prix_annuel' => null,
                'devise' => 'FCFA',
                'duree_essai' => 14,
                'statut' => 'ACTIF',
                'ordre_affichage' => 1,
            ]
        );

        $this->command->info('Offre de test créée.');
    }
}