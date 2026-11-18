<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GroupsCrudApiTest extends TestCase
{
    use RefreshDatabase;

    private User $director;
    private User $secretary;
    private User $teacherUserA;
    private Teacher $teacherA;
    private User $teacherUserB;
    private Teacher $teacherB;

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
        ]);

        $this->teacherUserA = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $this->teacherA = Teacher::factory()->create([
            'user_id' => $this->teacherUserA->id,
            'name' => 'Professeur A',
        ]);

        $this->teacherUserB = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $this->teacherB = Teacher::factory()->create([
            'user_id' => $this->teacherUserB->id,
            'name' => 'Professeur B',
        ]);
    }

    /**
     * Happy Path (200) : La secrétaire et le directeur peuvent lister tous les groupes.
     */
    public function test_staff_can_list_all_groups(): void
    {
        Sanctum::actingAs($this->secretary);
        Group::factory()->count(3)->create();

        $response = $this->getJson('/api/groups');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(3);
    }

    /**
     * Isolation / RBAC (200) : Un enseignant ne voit que les groupes qui lui sont assignés.
     */
    public function test_teacher_only_lists_own_groups(): void
    {
        Sanctum::actingAs($this->teacherUserA);

        // Groupe pour l'enseignant A
        Group::create([
            'name' => 'Groupe Math A',
            'teacher_id' => $this->teacherA->id,
            'level' => 'Baccalauréat',
        ]);

        // Groupe pour l'enseignant B
        Group::create([
            'name' => 'Groupe Physique B',
            'teacher_id' => $this->teacherB->id,
            'level' => 'Baccalauréat',
        ]);

        $response = $this->getJson('/api/groups');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Groupe Math A'])
            ->assertJsonMissing(['name' => 'Groupe Physique B']);
    }

    /**
     * Happy Path (201) : Création d'un nouveau groupe.
     */
    public function test_can_create_group(): void
    {
        Sanctum::actingAs($this->director);

        $payload = [
            'name' => 'Groupe 3ème Sciences C',
            'level' => '3ème Secondaire',
            'max_capacity' => 16,
            'notes' => 'Cours intensif de soutien',
        ];

        $response = $this->postJson('/api/groups', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Groupe 3ème Sciences C',
                'level' => '3ème Secondaire',
            ]);

        $this->assertDatabaseHas('groups', [
            'name' => 'Groupe 3ème Sciences C',
            'level' => '3ème Secondaire',
            'max_capacity' => 16,
        ]);
    }

    /**
     * Validation (422) : Échec si le nom du groupe est manquant.
     */
    public function test_create_group_validation_missing_name(): void
    {
        Sanctum::actingAs($this->director);

        $response = $this->postJson('/api/groups', [
            'level' => 'Baccalauréat',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Sécurité IDOR / RBAC (403) : Un enseignant ne peut pas modifier le groupe d'un autre enseignant.
     */
    public function test_teacher_cannot_update_group_belonging_to_another_teacher(): void
    {
        Sanctum::actingAs($this->teacherUserA);

        $groupB = Group::create([
            'name' => 'Groupe Propriété de B',
            'teacher_id' => $this->teacherB->id,
            'level' => 'Baccalauréat',
        ]);

        $response = $this->putJson("/api/groups/{$groupB->id}", [
            'name' => 'Nom Tenté par A',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Autorisation / RBAC (403) : La secrétaire ne peut pas supprimer un groupe (réservé directeur).
     */
    public function test_secretary_cannot_delete_group(): void
    {
        Sanctum::actingAs($this->secretary);
        $group = Group::factory()->create();

        $response = $this->deleteJson("/api/groups/{$group->id}");

        $response->assertStatus(403);
    }

    /**
     * Happy Path / RBAC (200) : Le directeur peut supprimer un groupe.
     */
    public function test_director_can_delete_group(): void
    {
        Sanctum::actingAs($this->director);
        $group = Group::factory()->create();

        $response = $this->deleteJson("/api/groups/{$group->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('groups', ['id' => $group->id]);
    }

    /**
     * Authentification (401) : Accès refusé pour invité.
     */
    public function test_unauthenticated_cannot_access_groups(): void
    {
        $response = $this->getJson('/api/groups');
        $response->assertStatus(401);
    }
}
