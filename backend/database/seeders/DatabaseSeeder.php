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


        // Compte Directeur
        User::updateOrCreate(
            ['email' => 'director@studycenter.com'],
            [
                'name' => 'Directeur',
                'password' => Hash::make('director123'),
                'role' => 'director',
                'is_active' => true,
            ]
        );



        // Compte Enseignant
        $teacherUser = User::updateOrCreate(
            ['email' => 'teacher@studycenter.com'],
            [
                'name' => 'Professeur Mohamed',
                'password' => Hash::make('teacher123'),
                'role' => 'teacher',
                'is_active' => true,
            ]
        );

        // Lier à un enseignant existant ou en créer un pour test
        $teacher = \App\Models\Teacher::firstOrCreate(
            ['email' => 'teacher@studycenter.com'],
            [
                'user_id' => $teacherUser->id,
                'name' => 'Professeur Mohamed',
                'subject' => 'Mathématiques, Physique',
                'phone' => '+216 20 000 000',
                'weekly_lessons' => 4,
                'salary_per_lesson' => 35.00,
                'fixed_student_rate' => 15.00,
                'center_fixed_per_student' => 20.00,
                'remuneration_type' => 'percentage',
                'rate_percentage' => 70.00,
            ]
        );

        if (!$teacher->user_id) {
            $teacher->user_id = $teacherUser->id;
            $teacher->save();
        }
    }
}
