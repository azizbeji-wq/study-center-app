<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResetBusinessDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_data_wipes_business_tables_and_preserves_users(): void
    {
        // 1. Créer des utilisateurs (connexion)
        $user1 = User::factory()->create([
            'email' => 'directeur@test.com',
            'role' => 'director',
        ]);
        $user2 = User::factory()->create([
            'email' => 'secretaire@test.com',
            'role' => 'secretary',
        ]);

        $token = $user1->createToken('test-token')->plainTextToken;
        $this->assertNotEmpty($token);

        // 2. Créer des données métier (students, teachers, payments, expenses)
        $teacher = Teacher::create([
            'name' => 'Professeur Test',
            'subject' => 'Mathématiques',
            'phone' => '98123456',
            'email' => 'prof@test.com',
            'remuneration_type' => 'fixed',
            'fixed_student_rate' => 20,
        ]);

        $student = Student::create([
            'name' => 'Ahmed Ben Ali',
            'phone' => '22113344',
            'level' => '1ère Année Secondaire',
        ]);

        Payment::create([
            'student_name' => 'Ahmed Ben Ali',
            'month' => '2026-10',
            'amount' => 120,
            'status' => 'paid',
        ]);

        Expense::create([
            'title' => 'Facture Électricité',
            'amount' => 150,
            'date' => now()->toDateString(),
            'category' => 'electricite',
        ]);

        // Vérification préalable que les données existent
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('teachers', 1);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // 3. Exécuter la commande de nettoyage
        $this->artisan('center:reset-data --force')
            ->expectsOutputToContain('Comptes utilisateurs conservés')
            ->assertExitCode(0);

        // 4. Vérifier que les tables métier sont complètement vidées
        $this->assertDatabaseCount('teachers', 0);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('expenses', 0);

        // 5. Vérifier que les tables de connexion sont STRICTEMENT INTACTES
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('users', ['email' => 'directeur@test.com']);
        $this->assertDatabaseHas('users', ['email' => 'secretaire@test.com']);
    }

    public function test_reset_data_aborts_when_not_confirmed(): void
    {
        User::factory()->create(['email' => 'admin@test.com']);
        Student::create([
            'name' => 'Foulen Ben Foulen',
            'phone' => '11223344',
            'level' => '3ème Année',
        ]);

        $this->artisan('center:reset-data')
            ->expectsConfirmation('ATTENTION: Vous êtes sur le point de supprimer TOUTES les données de test (élèves, profs, paiements, etc.). Continuer ?', 'no')
            ->expectsOutputToContain('Opération annulée par l’utilisateur.')
            ->assertExitCode(0);

        // Les données doivent être toujours là
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_reset_data_with_catalogs_clears_subjects_and_rooms_while_preserving_users(): void
    {
        User::factory()->create(['email' => 'admin2@test.com']);
        DB::table('subjects')->insert([
            'name' => 'Physique',
            'slug' => 'physique',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rooms')->insert([
            'name' => 'Salle B1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseCount('rooms', 1);

        $this->artisan('center:reset-data --force --with-catalogs')
            ->assertExitCode(0);

        $this->assertDatabaseCount('subjects', 0);
        $this->assertDatabaseCount('rooms', 0);
        $this->assertDatabaseCount('users', 1);
    }
}
