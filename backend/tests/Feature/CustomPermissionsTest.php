<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $director;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::create([
            'name'      => 'Directeur Fondateur',
            'email'     => 'director@studycenter.com',
            'password'  => Hash::make('password123'),
            'role'      => 'director',
            'is_active' => true,
        ]);
    }

    public function test_director_can_create_user_with_custom_permissions(): void
    {
        $response = $this->actingAs($this->director)->postJson('/api/users', [
            'name'        => 'Trésorier Partiel',
            'email'       => 'tresorier@studycenter.com',
            'password'    => 'secret123',
            'role'        => 'secretary',
            'permissions' => ['payments', 'reports'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.name', 'Trésorier Partiel')
            ->assertJsonPath('user.permissions', ['payments', 'reports']);

        $user = User::where('email', 'tresorier@studycenter.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasPermission('payments'));
        $this->assertTrue($user->hasPermission('reports'));
        $this->assertFalse($user->hasPermission('students'));
    }

    public function test_user_with_reports_permission_can_access_accounting_summary(): void
    {
        $financeUser = User::create([
            'name'        => 'Responsable Caisse',
            'email'       => 'caisse@studycenter.com',
            'password'    => Hash::make('password123'),
            'role'        => 'secretary',
            'permissions' => ['payments', 'reports'],
            'is_active'   => true,
        ]);

        // Ce membre du personnel a la permission reports -> doit pouvoir accéder
        $response = $this->actingAs($financeUser)->getJson('/api/accounting/summary');
        $response->assertStatus(200);

        // Un membre du personnel standard SANS la permission reports doit être bloqué
        $regularUser = User::create([
            'name'        => 'Accueil Simple',
            'email'       => 'accueil@studycenter.com',
            'password'    => Hash::make('password123'),
            'role'        => 'secretary',
            'permissions' => ['students', 'attendance'],
            'is_active'   => true,
        ]);

        $deniedResponse = $this->actingAs($regularUser)->getJson('/api/accounting/summary');
        $deniedResponse->assertStatus(403);
    }

    public function test_user_with_can_delete_permission_can_delete_student(): void
    {
        $student = Student::create([
            'name'       => 'Youssef Test',
            'level'      => '6ème',
            'phone'      => '12345678',
            'created_by' => $this->director->id,
        ]);

        $deleteAuthorizedUser = User::create([
            'name'        => 'Superviseur Autorisé',
            'email'       => 'superviseur@studycenter.com',
            'password'    => Hash::make('password123'),
            'role'        => 'secretary',
            'permissions' => ['students', 'can_delete'],
            'is_active'   => true,
        ]);

        $res = $this->actingAs($deleteAuthorizedUser)->deleteJson("/api/students/{$student->id}");
        $res->assertStatus(200);

        // Créer un autre étudiant pour tester le refus
        $student2 = Student::create([
            'name'       => 'Salma Test',
            'level'      => '5ème',
            'phone'      => '87654321',
            'created_by' => $this->director->id,
        ]);

        $nonAuthorizedUser = User::create([
            'name'        => 'Sans Droit Suppression',
            'email'       => 'nodelete@studycenter.com',
            'password'    => Hash::make('password123'),
            'role'        => 'secretary',
            'permissions' => ['students'], // Pas de can_delete
            'is_active'   => true,
        ]);

        $deniedRes = $this->actingAs($nonAuthorizedUser)->deleteJson("/api/students/{$student2->id}");
        $deniedRes->assertStatus(403);
    }
}
