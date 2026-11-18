<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
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
            'permissions' => ['attendance', 'dashboard'], // Pas de 'can_delete'
        ]);
    }

    /**
     * Happy Path (200) : Liste des présences avec structure JSON.
     */
    public function test_can_list_attendances(): void
    {
        Sanctum::actingAs($this->secretary);
        Attendance::factory()->count(3)->create();

        $response = $this->getJson('/api/attendances');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'student_id',
                    'teacher_id',
                    'session_date',
                    'status',
                ]
            ]);
    }

    /**
     * Authentification (401) : Accès refusé pour invité.
     */
    public function test_unauthenticated_cannot_access_attendances(): void
    {
        $response = $this->getJson('/api/attendances');
        $response->assertStatus(401);
    }

    /**
     * Happy Path (201) : Enregistrement unitaire d'une présence.
     */
    public function test_can_store_single_attendance(): void
    {
        Sanctum::actingAs($this->secretary);
        $student = Student::factory()->create();
        $teacher = Teacher::factory()->create();

        $payload = [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'group_name' => 'Groupe Test',
            'subject_name' => 'Mathématiques',
            'session_date' => '2026-11-16',
            'status' => 'present',
            'notes' => 'À l\'heure et participatif',
        ];

        $response = $this->postJson('/api/attendances', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'student_id' => $student->id,
                'status' => 'present',
                'subject_name' => 'Mathématiques',
            ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'session_date' => '2026-11-16',
            'status' => 'present',
        ]);
    }

    /**
     * Happy Path (200) : Enregistrement par lot (Batch Attendance) pour un groupe entier.
     */
    public function test_can_record_batch_attendance(): void
    {
        Sanctum::actingAs($this->secretary);
        $teacher = Teacher::factory()->create();
        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();

        $payload = [
            'session_date' => '2026-11-16',
            'subject_name' => 'Physique',
            'teacher_id' => $teacher->id,
            'group_name' => 'Groupe Terminale',
            'records' => [
                ['student_id' => $studentA->id, 'status' => 'present', 'notes' => 'Présent'],
                ['student_id' => $studentB->id, 'status' => 'absent', 'notes' => 'Non justifié'],
            ],
        ];

        $response = $this->postJson('/api/attendances/batch', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Présences enregistrées avec succès',
                'count' => 2,
            ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $studentA->id,
            'session_date' => '2026-11-16',
            'status' => 'present',
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $studentB->id,
            'session_date' => '2026-11-16',
            'status' => 'absent',
        ]);
    }

    /**
     * Validation (422) : Échec si le statut de présence est invalide.
     */
    public function test_store_attendance_validation_invalid_status(): void
    {
        Sanctum::actingAs($this->secretary);
        $student = Student::factory()->create();

        $response = $this->postJson('/api/attendances', [
            'student_id' => $student->id,
            'subject_name' => 'Philosophie',
            'session_date' => '2026-11-16',
            'status' => 'dormeur', // Statut non autorisé (valides: present, absent, excused, late)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Happy Path (200) : Synthèse des présences d'un élève.
     */
    public function test_can_get_student_attendance_summary(): void
    {
        Sanctum::actingAs($this->secretary);
        $student = Student::factory()->create();

        Attendance::create([
            'student_id' => $student->id,
            'subject_name' => 'Histoire',
            'session_date' => '2026-11-10',
            'status' => 'present',
        ]);
        Attendance::create([
            'student_id' => $student->id,
            'subject_name' => 'Histoire',
            'session_date' => '2026-11-12',
            'status' => 'absent',
        ]);

        $response = $this->getJson("/api/attendances/summary/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'student' => ['id', 'name', 'level'],
                'attendance_count' => [
                    'total',
                    'present',
                    'absent',
                ],
                'calculation' => [
                    'gross_fee',
                    'discount_amount',
                    'amount_to_pay',
                ],
            ])
            ->assertJson([
                'student' => [
                    'id' => $student->id,
                ],
                'attendance_count' => [
                    'total' => 2,
                    'present' => 1,
                    'absent' => 1,
                ],
            ]);
    }

    /**
     * Autorisation / RBAC (403) : Secrétaire sans can_delete ne peut pas supprimer une présence.
     */
    public function test_secretary_cannot_delete_attendance(): void
    {
        Sanctum::actingAs($this->secretary);
        $attendance = Attendance::factory()->create();

        $response = $this->deleteJson("/api/attendances/{$attendance->id}");

        $response->assertStatus(403);
    }

    /**
     * Happy Path / RBAC (200) : Le directeur peut supprimer une fiche de présence.
     */
    public function test_director_can_delete_attendance(): void
    {
        Sanctum::actingAs($this->director);
        $attendance = Attendance::factory()->create();

        $response = $this->deleteJson("/api/attendances/{$attendance->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }
}
