<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $director;
    private User $secretary;
    private User $teacherUser;
    private Teacher $teacherModel;
    private Group $teacherGroup;
    private Student $enrolledStudent;
    private Student $otherStudent;
    private Teacher $otherTeacher;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Directeur
        $this->director = User::create([
            'name' => 'Directeur Général',
            'email' => 'director@studycenter.tn',
            'password' => bcrypt('password123'),
            'role' => 'director',
            'is_active' => true,
        ]);

        // 2. Secrétaire
        $this->secretary = User::create([
            'name' => 'Secrétaire Accueil',
            'email' => 'secretary@studycenter.tn',
            'password' => bcrypt('password123'),
            'role' => 'secretary',
            'is_active' => true,
        ]);

        // 3. Enseignant
        $this->teacherUser = User::create([
            'name' => 'Professeur Sami',
            'email' => 'sami.prof@studycenter.tn',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->teacherModel = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'name' => 'Professeur Sami',
            'email' => 'sami.prof@studycenter.tn',
            'subject' => 'Mathématiques',
            'phone' => '20111222',
        ]);

        // Autre enseignant
        $this->otherTeacher = Teacher::create([
            'name' => 'Professeure Leila',
            'email' => 'leila.prof@studycenter.tn',
            'subject' => 'Physique-Chimie',
            'phone' => '20333444',
        ]);

        // 4. Groupe de l'enseignant Sami
        $this->teacherGroup = Group::create([
            'name' => 'Groupe Sami Math',
            'teacher_id' => $this->teacherModel->id,
            'level' => 'Baccalauréat',
        ]);

        // 5. Élève faisant partie du groupe de Sami
        $this->enrolledStudent = Student::create([
            'name' => 'Yassine Belhadj',
            'level' => 'Baccalauréat',
            'group_name' => 'Groupe Sami Math',
            'phone' => '98111222',
            'parent_phone' => '98333444',
            'status' => 'active',
        ]);
        $this->teacherGroup->students()->attach($this->enrolledStudent->id);

        // 6. Élève hors des groupes de Sami
        $this->otherStudent = Student::create([
            'name' => 'Yassine Trabelsi',
            'level' => 'Baccalauréat',
            'group_name' => 'Groupe Externe',
            'phone' => '99111222',
            'parent_phone' => '99333444',
            'status' => 'active',
        ]);
    }

    /**
     * Teste qu'une requête non authentifiée est rejetée (401).
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/search?q=Yassine');
        $response->assertStatus(401);
    }

    /**
     * Teste qu'un terme q de moins de 2 caractères est rejeté avec erreur 422.
     */
    public function test_query_less_than_two_characters_is_rejected(): void
    {
        $response = $this->actingAs($this->director)
            ->getJson('/api/search?q=a');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    }

    /**
     * Teste qu'une recherche vide (sans paramètre q) est rejetée.
     */
    public function test_missing_query_parameter_is_rejected(): void
    {
        $response = $this->actingAs($this->director)
            ->getJson('/api/search');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    }

    /**
     * Teste que le directeur reçoit tous les élèves et enseignants correspondants.
     */
    public function test_director_can_search_all_students_and_teachers(): void
    {
        $response = $this->actingAs($this->director)
            ->getJson('/api/search?q=Yassine');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('students', $data);
        $this->assertArrayHasKey('teachers', $data);

        // Les deux élèves doivent apparaître
        $studentNames = collect($data['students'])->pluck('name')->toArray();
        $this->assertContains('Yassine Belhadj', $studentNames);
        $this->assertContains('Yassine Trabelsi', $studentNames);

        // Actions autorisées pour le directeur
        $this->assertTrue($data['students'][0]['can_pay']);
        $this->assertTrue($data['students'][0]['can_statement']);
    }

    /**
     * Teste la recherche d'enseignants pour le directeur et la secrétaire.
     */
    public function test_director_and_secretary_can_search_teachers(): void
    {
        $response = $this->actingAs($this->secretary)
            ->getJson('/api/search?q=Leila');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertNotEmpty($data['teachers']);
        $this->assertEquals('Professeure Leila', $data['teachers'][0]['name']);
    }

    /**
     * Teste qu'un enseignant ne reçoit JAMAIS d'élève hors de ses groupes
     * et ne reçoit jamais les enseignants.
     */
    public function test_teacher_never_receives_students_outside_own_groups(): void
    {
        // Recherche le prénom commun "Yassine"
        $response = $this->actingAs($this->teacherUser)
            ->getJson('/api/search?q=Yassine');

        $response->assertStatus(200);
        $data = $response->json();

        // Doit contenir uniquement l'élève inscrit dans son groupe
        $studentNames = collect($data['students'])->pluck('name')->toArray();
        $this->assertContains('Yassine Belhadj', $studentNames);
        $this->assertNotContains('Yassine Trabelsi', $studentNames);

        // Les enseignants ne doivent jamais être retournés à un enseignant
        $this->assertEmpty($data['teachers']);

        // Les actions d'encaissement et extrait doivent être fausses pour un enseignant
        $this->assertFalse($data['students'][0]['can_pay']);
        $this->assertFalse($data['students'][0]['can_statement']);
    }

    /**
     * Teste la limite maximale de 10 résultats par type.
     */
    public function test_search_results_are_limited_to_ten(): void
    {
        // Créer 15 élèves avec le même préfixe
        for ($i = 1; $i <= 15; $i++) {
            Student::create([
                'name' => "Eleve Test Batch {$i}",
                'level' => 'Collège',
                'phone' => "200000{$i}",
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($this->director)
            ->getJson('/api/search?q=Batch');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(10, $data['students']);
    }
}
