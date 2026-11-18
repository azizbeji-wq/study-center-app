<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $director;
    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::factory()->create([
            'role' => 'director',
            'is_active' => true,
        ]);

        $this->secretary = User::factory()->create([
            'role' => 'secretary',
            'is_active' => true,
            'permissions' => ['students', 'dashboard'], // Ne contient pas 'can_delete'
        ]);
    }

    /**
     * Happy Path (200) : Récupération de la liste des élèves.
     */
    public function test_can_list_students(): void
    {
        Sanctum::actingAs($this->secretary);
        Student::factory()->count(3)->create();

        $response = $this->getJson('/api/students');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'level',
                    'phone',
                    'status',
                    'subjects',
                ]
            ]);
    }

    /**
     * Authentification (401) : Accès refusé sans token.
     */
    public function test_unauthenticated_cannot_list_students(): void
    {
        $response = $this->getJson('/api/students');

        $response->assertStatus(401);
    }

    /**
     * Happy Path (201) : Inscription réussie d'un élève avec matière et enseignant.
     */
    public function test_can_create_student_with_enrollment(): void
    {
        Sanctum::actingAs($this->secretary);
        $teacher = Teacher::factory()->create(['salary_per_lesson' => 35.00]);

        $payload = [
            'name' => 'Mohamed Trabelsi',
            'level' => 'Baccalauréat',
            'group_name' => 'Bac Informatique',
            'phone' => '+216 98 111 222',
            'parent_phone' => '+216 98 333 444',
            'status' => 'active',
            'is_remise' => true,
            'remise' => 15.0,
            'discount_type' => 'percentage',
            'base_fee' => 150.0,
            'subjects' => [
                [
                    'subject' => 'Mathématiques',
                    'teacher_id' => $teacher->id,
                    'group_name' => 'Bac Informatique',
                    'sessions_per_month' => 4,
                    'price_per_month' => 140.0,
                    'price_per_session' => 35.0,
                ],
            ],
        ];

        $response = $this->postJson('/api/students', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'level',
                'phone',
                'subjects',
            ])
            ->assertJson([
                'name' => 'Mohamed Trabelsi',
                'level' => 'Baccalauréat',
                'group_name' => 'Bac Informatique',
            ]);

        $this->assertDatabaseHas('students', [
            'name' => 'Mohamed Trabelsi',
            'phone' => '+216 98 111 222',
            'is_remise' => true,
            'remise' => 15.0,
        ]);

        $this->assertDatabaseHas('student_subject_enrollments', [
            'teacher_id' => $teacher->id,
            'subject_name' => 'Mathématiques',
            'group_name' => 'Bac Informatique',
        ]);
    }

    /**
     * Validation (422) : Échec si des champs obligatoires sont absents.
     */
    public function test_create_student_validation_required_fields(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->postJson('/api/students', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'level', 'phone']);
    }

    /**
     * Validation (422) : Statut invalide ou enseignant inexistant.
     */
    public function test_create_student_validation_invalid_data(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->postJson('/api/students', [
            'name' => 'Test Invalide',
            'level' => '3ème',
            'phone' => '12345678',
            'status' => 'status_invalide',
            'remise' => -10,
            'subjects' => [
                [
                    'subject' => 'Physique',
                    'teacher_id' => 999999, // Enseignant inexistant
                ]
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status', 'remise', 'subjects.0.teacher_id']);
    }

    /**
     * Happy Path (200) : Consultation d'un élève par ID.
     */
    public function test_can_show_student(): void
    {
        Sanctum::actingAs($this->secretary);
        $student = Student::factory()->create(['name' => 'Sarra Ben Ahmed']);

        $response = $this->getJson("/api/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $student->id,
                'name' => 'Sarra Ben Ahmed',
            ]);
    }

    /**
     * Happy Path (200) : Mise à jour d'un élève.
     */
    public function test_can_update_student(): void
    {
        Sanctum::actingAs($this->secretary);
        $student = Student::factory()->create(['name' => 'Ancien Nom']);

        $response = $this->putJson("/api/students/{$student->id}", [
            'name' => 'Nouveau Nom Mis à Jour',
            'level' => 'Bac Sciences',
            'phone' => '+216 22 999 888',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Nouveau Nom Mis à Jour',
            ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Nouveau Nom Mis à Jour',
            'phone' => '+216 22 999 888',
        ]);
    }

    /**
     * Autorisation / RBAC (403) : La secrétaire sans la permission can_delete ne peut pas supprimer un élève.
     */
    public function test_secretary_cannot_delete_student_without_permission(): void
    {
        Sanctum::actingAs($this->secretary);
        $student = Student::factory()->create();

        $response = $this->deleteJson("/api/students/{$student->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Happy Path / RBAC (200) : Le directeur peut supprimer un élève (Soft Delete).
     */
    public function test_director_can_delete_student_soft_deletes(): void
    {
        Sanctum::actingAs($this->director);
        $student = Student::factory()->create();

        $response = $this->deleteJson("/api/students/{$student->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('students', [
            'id' => $student->id,
        ]);
    }
}
