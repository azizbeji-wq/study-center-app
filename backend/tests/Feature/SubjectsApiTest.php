<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubjectsApiTest extends TestCase
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
            'permissions' => ['students'],
        ]);
    }

    /**
     * Happy Path (200) : Consultation des matières par le personnel connecté.
     */
    public function test_can_list_subjects(): void
    {
        Sanctum::actingAs($this->secretary);
        Subject::factory()->create(['name' => 'Informatique']);

        $response = $this->getJson('/api/subjects');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonFragment([
                'name' => 'Informatique',
            ]);
    }

    /**
     * Authentification (401) : Accès refusé pour un invité non connecté.
     */
    public function test_unauthenticated_cannot_access_subjects(): void
    {
        $response = $this->getJson('/api/subjects');
        $response->assertStatus(401);
    }

    /**
     * Happy Path / RBAC (201) : Le directeur crée une nouvelle matière.
     */
    public function test_director_can_create_subject(): void
    {
        Sanctum::actingAs($this->director);

        $payload = [
            'name' => 'Histoire-Géographie',
            'color' => '#10B981',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/subjects', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Histoire-Géographie',
                'color' => '#10B981',
            ]);

        $this->assertDatabaseHas('subjects', [
            'name' => 'Histoire-Géographie',
            'color' => '#10B981',
        ]);
    }

    /**
     * Autorisation / RBAC (403) : La secrétaire ne peut pas créer de matière.
     */
    public function test_secretary_cannot_create_subject(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->postJson('/api/subjects', [
            'name' => 'Matière Interdite',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Validation (422) : Échec si le code couleur hex est invalide ou slug dupliqué.
     */
    public function test_create_subject_validation_errors(): void
    {
        Sanctum::actingAs($this->director);

        Subject::factory()->create(['name' => 'Économie', 'slug' => 'economie']);

        $response = $this->postJson('/api/subjects', [
            'name' => 'Économie', // Déjà existant
            'color' => 'bleu-ciel', // Doit être un format hexadécimal #RRGGBB
        ]);

        $response->assertStatus(422);
    }

    /**
     * Happy Path / RBAC (200) : Le directeur peut supprimer une matière sans inscriptions.
     */
    public function test_director_can_delete_unused_subject(): void
    {
        Sanctum::actingAs($this->director);
        $subject = Subject::factory()->create(['name' => 'Italien']);

        $response = $this->deleteJson("/api/subjects/{$subject->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Matière supprimée avec succès.']);

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    /**
     * Sécurité Métier (422) : Refus de suppression d'une matière liée à des inscriptions d'élèves.
     */
    public function test_cannot_delete_subject_with_active_enrollments(): void
    {
        Sanctum::actingAs($this->director);
        $subject = Subject::factory()->create(['name' => 'Allemand']);
        $teacher = \App\Models\Teacher::factory()->create();
        $student = Student::factory()->create();

        StudentSubjectEnrollment::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'subject_name' => 'Allemand',
            'price_per_month' => 90.0,
        ]);

        $response = $this->deleteJson("/api/subjects/{$subject->id}");

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Impossible de supprimer : 1 inscription(s) sont liées à cette matière.',
            ]);

        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }
}
