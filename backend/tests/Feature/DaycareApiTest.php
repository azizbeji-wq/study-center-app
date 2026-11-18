<?php

namespace Tests\Feature;

use App\Models\DaycareChild;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DaycareApiTest extends TestCase
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
            'permissions' => ['daycare', 'dashboard'], // Pas de 'can_delete'
        ]);
    }

    /**
     * Happy Path (200) : Récupération de la liste des enfants en garderie.
     */
    public function test_can_list_daycare_children(): void
    {
        Sanctum::actingAs($this->secretary);
        DaycareChild::factory()->count(3)->create();

        $response = $this->getJson('/api/daycare');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'age',
                    'parentName',
                    'phone',
                    'schedule',
                    'status',
                    'monthlyFee',
                ]
            ]);
    }

    /**
     * Authentification (401) : Accès refusé pour invité.
     */
    public function test_unauthenticated_cannot_access_daycare(): void
    {
        $response = $this->getJson('/api/daycare');
        $response->assertStatus(401);
    }

    /**
     * Happy Path (201) : Inscription d'un enfant en garderie.
     */
    public function test_can_create_daycare_child(): void
    {
        Sanctum::actingAs($this->secretary);

        $payload = [
            'name' => 'Adam Ben Salah',
            'age' => 5,
            'parentName' => 'Mounir Ben Salah',
            'phone' => '+216 20 555 777',
            'schedule' => '8h00 - 18h00',
            'status' => 'present',
            'monthlyFee' => 90.0,
            'startDate' => '2026-11-01',
        ];

        $response = $this->postJson('/api/daycare', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'age',
                'parentName',
                'phone',
                'schedule',
                'status',
                'monthlyFee',
            ])
            ->assertJson([
                'name' => 'Adam Ben Salah',
                'age' => 5,
                'parentName' => 'Mounir Ben Salah',
                'monthlyFee' => 90.0,
            ]);

        $this->assertDatabaseHas('daycare_children', [
            'name' => 'Adam Ben Salah',
            'parent_name' => 'Mounir Ben Salah',
            'phone' => '+216 20 555 777',
            'monthly_fee' => 90.0,
        ]);
    }

    /**
     * Validation (422) : Échec si des champs obligatoires sont manquants.
     */
    public function test_create_daycare_child_validation_required(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->postJson('/api/daycare', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'age', 'parentName', 'phone', 'schedule']);
    }

    /**
     * Validation (422) : Statut de présence invalide.
     */
    public function test_create_daycare_child_validation_invalid_status(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->postJson('/api/daycare', [
            'name' => 'Enfant Test',
            'age' => 4,
            'parentName' => 'Parent Test',
            'phone' => '12345678',
            'schedule' => 'Matin',
            'status' => 'en_route', // Seuls 'present' et 'absent' sont autorisés
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Happy Path (200) : Modification d'un enfant en garderie.
     */
    public function test_can_update_daycare_child(): void
    {
        Sanctum::actingAs($this->secretary);
        $child = DaycareChild::factory()->create(['name' => 'Ancien Enfant', 'age' => 4]);

        $response = $this->putJson("/api/daycare/{$child->id}", [
            'name' => 'Enfant Mis à Jour',
            'age' => 5,
            'schedule' => 'Temps plein',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Enfant Mis à Jour',
                'age' => 5,
            ]);

        $this->assertDatabaseHas('daycare_children', [
            'id' => $child->id,
            'name' => 'Enfant Mis à Jour',
            'age' => 5,
        ]);
    }

    /**
     * Autorisation / RBAC (403) : Secrétaire sans can_delete ne peut pas supprimer un enfant.
     */
    public function test_secretary_cannot_delete_daycare_child(): void
    {
        Sanctum::actingAs($this->secretary);
        $child = DaycareChild::factory()->create();

        $response = $this->deleteJson("/api/daycare/{$child->id}");

        $response->assertStatus(403);
    }

    /**
     * Happy Path / RBAC (200) : Le directeur peut supprimer un enfant (Soft Delete).
     */
    public function test_director_can_delete_daycare_child(): void
    {
        Sanctum::actingAs($this->director);
        $child = DaycareChild::factory()->create();

        $response = $this->deleteJson("/api/daycare/{$child->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('daycare_children', ['id' => $child->id]);
    }
}
