<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\ScheduleItem;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherPortalApiTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacherUser = User::factory()->create([
            'name' => 'Professeur Mehdi',
            'email' => 'prof.mehdi@studycenter.com',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->teacher = Teacher::factory()->create([
            'user_id' => $this->teacherUser->id,
            'name' => 'Professeur Mehdi',
            'email' => 'prof.mehdi@studycenter.com',
            'subject' => 'Sciences SVT',
        ]);
    }

    /**
     * Happy Path (200) : Consultation du profil enseignant connecté.
     */
    public function test_teacher_can_view_own_portal_profile(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson('/api/teacher/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role'],
                'teacher' => ['id', 'name', 'subject'],
                'is_linked',
            ])
            ->assertJson([
                'is_linked' => true,
                'user' => [
                    'id' => $this->teacherUser->id,
                    'role' => 'teacher',
                ],
                'teacher' => [
                    'id' => $this->teacher->id,
                    'name' => 'Professeur Mehdi',
                ],
            ]);
    }

    /**
     * Happy Path (200) : Consultation de l'emploi du temps de l'enseignant.
     */
    public function test_teacher_can_view_own_schedule(): void
    {
        Sanctum::actingAs($this->teacherUser);

        ScheduleItem::create([
            'teacher' => 'Professeur Mehdi',
            'subject' => 'Sciences SVT',
            'level' => 'Baccalauréat',
            'day' => 'Mardi',
            'time' => '10:00 - 12:00',
            'room' => 'Salle B1',
            'group_name' => 'Groupe SVT Bac',
        ]);

        $response = $this->getJson('/api/teacher/schedule');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'teacher' => 'Professeur Mehdi',
                'day' => 'Mardi',
                'subject' => 'Sciences SVT',
            ]);
    }

    /**
     * Happy Path (200) : Consultation des groupes assignés à l'enseignant.
     */
    public function test_teacher_can_view_assigned_groups(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $group = Group::create([
            'name' => 'Groupe SVT Bac',
            'teacher_id' => $this->teacher->id,
            'level' => 'Baccalauréat',
            'subject' => 'Sciences SVT',
            'max_capacity' => 15,
        ]);

        $student = Student::factory()->create(['group_name' => 'Groupe SVT Bac']);
        $group->students()->attach($student->id);

        $response = $this->getJson('/api/teacher/groups');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonFragment([
                'group_name' => 'Groupe SVT Bac',
            ]);
    }

    /**
     * Happy Path (200) : Liste des élèves d'un groupe pour pointage d'appel.
     */
    public function test_teacher_can_view_students_in_group(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $student = Student::factory()->create([
            'name' => 'Khadija Mansour',
            'group_name' => 'Groupe SVT Bac',
        ]);

        StudentSubjectEnrollment::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacher->id,
            'subject_name' => 'Sciences SVT',
            'group_name' => 'Groupe SVT Bac',
            'sessions_per_month' => 4,
            'price_per_month' => 120.0,
            'price_per_session' => 30.0,
        ]);

        $response = $this->getJson('/api/teacher/groups/Groupe%20SVT%20Bac/students');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $student->id,
                'name' => 'Khadija Mansour',
            ]);
    }

    /**
     * Happy Path (200) : Enregistrement de l'appel par lot depuis le portail enseignant.
     */
    public function test_teacher_can_submit_batch_attendance(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $student = Student::factory()->create(['name' => 'Eleve Test']);

        $payload = [
            'session_date' => now()->toDateString(),
            'subject_name' => 'Sciences SVT',
            'group_name' => 'Groupe SVT Bac',
            'records' => [
                [
                    'student_id' => $student->id,
                    'status' => 'present',
                    'notes' => 'Présent au cours SVT',
                ],
            ],
        ];

        $response = $this->postJson('/api/teacher/attendance/batch', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Présences enregistrées avec succès.',
                'count' => 1,
            ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'teacher_id' => $this->teacher->id,
            'subject_name' => 'Sciences SVT',
            'status' => 'present',
        ]);
    }

    /**
     * Authentification (401) : Accès refusé pour les requêtes non authentifiées.
     */
    public function test_unauthenticated_cannot_access_teacher_portal(): void
    {
        $response = $this->getJson('/api/teacher/profile');
        $response->assertStatus(401);
    }
}
