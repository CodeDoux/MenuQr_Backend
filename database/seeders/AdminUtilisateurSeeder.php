<?php

namespace Database\Seeders;

use App\Models\AdminUtilisateur;
use Illuminate\Database\Seeder;

class AdminUtilisateurSeeder extends Seeder
{
    public function run(): void
    {
        AdminUtilisateur::updateOrCreate(
            ['email' => 'admin@menuqr.com'],
            ['nom_complet' => 'Équipe MenuQr', 'password' => 'admin123', 'actif' => true]
        );

        $this->command->info('Compte admin par défaut : admin@menuqr.com / admin123');
    }
}