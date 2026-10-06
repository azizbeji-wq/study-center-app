<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Ne conserve STRICTEMENT que les comptes Directeur et Secrétaire, de manière idempotente.
     */
    public function run(): void
    {
        // 1. Compte Directeur (Accès Maître)
        User::updateOrCreate(
            ['email' => 'directeur@studycenter.com'],
            [
                'name' => 'Directeur',
                'password' => Hash::make('director123'),
                'role' => 'director',
                'is_active' => true,
            ]
        );

        // Alias director@ pour compatibilité
        User::updateOrCreate(
            ['email' => 'director@studycenter.com'],
            [
                'name' => 'Directeur',
                'password' => Hash::make('director123'),
                'role' => 'director',
                'is_active' => true,
            ]
        );

        // 2. Compte Secrétaire (Personnel d'accueil)
        User::updateOrCreate(
            ['email' => 'secretaire@studycenter.com'],
            [
                'name' => 'Secrétaire',
                'password' => Hash::make('secretary123'),
                'role' => 'secretary',
                'is_active' => true,
            ]
        );

        // Alias secretary@ pour compatibilité
        User::updateOrCreate(
            ['email' => 'secretary@studycenter.com'],
            [
                'name' => 'Secrétaire',
                'password' => Hash::make('secretary123'),
                'role' => 'secretary',
                'is_active' => true,
            ]
        );
    }
}
