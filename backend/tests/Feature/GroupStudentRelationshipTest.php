<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupStudentRelationshipTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Group $group;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur autorisé
        $this->user = User::create([
            'name' => 'Secrétaire Admin',
            'email' => 'secretary@studycenter.com',
            'password' => bcrypt('secret123'),
            'role' => 'secretary',
            'is_active' => true,
        ]);

        // Créer un groupe
        $this->group = Group::create([
            'name' => 'Groupe Bac Sciences A',
            'level' => 'Baccalauréat',
            'max_capacity' => 12,
        ]);

        // Créer un élève
        $this->student = Student::create([
            'name' => 'Yasmine Ben Salem',
            'level' => 'Baccalauréat',
            'phone' => '+216 98 123 456',
            'status' => 'active',
        ]);
    }

    /**
     * Teste les relations Eloquent belongsToMany dans les deux sens (Student <-> Group).
     */
    public function test_eloquent_belongs_to_many_relationships(): void
    {
        // 1. Attacher via Eloquent
        $this->group->students()->attach($this->student->id);

        // 2. Vérifier depuis le modèle Group
        $this->assertTrue($this->group->students->contains($this->student));
        $this->assertEquals(1, $this->group->students()->count());

        // 3. Vérifier depuis le modèle Student
        $this->assertTrue($this->student->groups->contains($this->group));
        $this->assertEquals(1, $this->student->groups()->count());
    }

    /**
     * Teste la méthode attachStudent du contrôleur avec succès.
     */
    public function test_attach_student_controller_method_attaches_student(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->id}/students", [
                'student_id' => $this->student->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'group' => [
                'id' => $this->group->id,
                'students_count' => 1,
            ],
            'student' => [
                'id' => $this->student->id,
                'name' => 'Yasmine Ben Salem',
            ],
        ]);

        // Vérifier l'insertion dans la table pivot group_student
        $this->assertDatabaseHas('group_student', [
            'group_id' => $this->group->id,
            'student_id' => $this->student->id,
        ]);
    }

    /**
     * Teste la validation : rejet si student_id est manquant ou inexistant.
     */
    public function test_attach_student_validates_student_id(): void
    {
        // student_id manquant
        $resMissing = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->id}/students", []);

        $resMissing->assertStatus(422);
        $resMissing->assertJsonValidationErrors(['student_id']);

        // student_id inexistant
        $resNotFound = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->id}/students", [
                'student_id' => 999999,
            ]);

        $resNotFound->assertStatus(422);
        $resNotFound->assertJsonValidationErrors(['student_id']);
    }

    /**
     * Teste la prévention des doublons : refuser d'attacher un élève déjà présent dans le groupe.
     */
    public function test_attach_student_prevents_duplicate_attachment(): void
    {
        // 1. Premier attachement réussi
        $this->group->students()->attach($this->student->id);

        // 2. Deuxième tentative d'attachement du même élève
        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->id}/students", [
                'student_id' => $this->student->id,
            ]);

        // Réponse 409 Conflict avec statut warning
        $response->assertStatus(409);
        $response->assertJson([
            'status' => 'warning',
            'message' => "L'élève {$this->student->name} est déjà inscrit dans le groupe {$this->group->name}.",
        ]);

        // Vérifier qu'il n'y a qu'une seule ligne dans la table pivot
        $this->assertDatabaseCount('group_student', 1);
    }

    /**
     * Teste qu'un enseignant peut créer son propre groupe via POST /api/groups
     * avec assignation automatique de teacher_id = auth()->id().
     */
    public function test_teacher_can_create_group_with_auto_assigned_teacher_id(): void
    {
        $teacherUser = User::create([
            'name' => 'Professeur Mohamed',
            'email' => 'teacher.mohamed@studycenter.com',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Groupe Terminale Math A',
            'level' => 'Baccalauréat',
            'subject' => 'Mathématiques',
            'max_capacity' => 15,
        ];

        $response = $this->actingAs($teacherUser)
            ->postJson('/api/groups', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'name' => 'Groupe Terminale Math A',
            'level' => 'Baccalauréat',
            'subject' => 'Mathématiques',
            'teacher_id' => $teacherUser->id,
        ]);

        $this->assertDatabaseHas('groups', [
            'name' => 'Groupe Terminale Math A',
            'level' => 'Baccalauréat',
            'subject' => 'Mathématiques',
            'teacher_id' => $teacherUser->id,
        ]);
    }

    /**
     * Teste que le groupe créé apparaît bien dans le portail enseignant /api/teacher/groups.
     */
    public function test_teacher_can_fetch_groups_including_newly_created(): void
    {
        $teacherUser = User::create([
            'name' => 'Professeur Mohamed',
            'email' => 'teacher.mohamed2@studycenter.com',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        \App\Models\Teacher::create([
            'user_id' => $teacherUser->id,
            'name' => 'Professeur Mohamed',
            'subject' => 'Mathématiques',
            'phone' => '+216 20 000 000',
            'email' => 'teacher.mohamed2@studycenter.com',
        ]);

        $this->actingAs($teacherUser)
            ->postJson('/api/groups', [
                'name' => 'Groupe Pilote',
                'level' => 'Baccalauréat',
                'subject' => 'Mathématiques',
            ])->assertStatus(201);

        $response = $this->actingAs($teacherUser)
            ->getJson('/api/teacher/groups');

        $response->assertStatus(200);
        $groups = $response->json();
        $this->assertNotEmpty($groups);
        $this->assertEquals('Groupe Pilote', $groups[0]['group_name']);
    }
}

