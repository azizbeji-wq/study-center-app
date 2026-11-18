<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeachersApiTest extends TestCase
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
            'permissions' => ['teachers', 'dashboard'], // Pas de 'can_delete'
        ]);
    }

    /**
     * Happy Path (200) : Récupération de la liste des enseignants.
     */
    public function test_can_list_teachers(): void
    {
        Sanctum::actingAs($this->secretary);
        Teacher::factory()->count(2)->create();

        $response = $this->getJson('/api/teachers');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'subject',
                    'phone',
                    'email',
                ]
            ]);
    }

    /**
     * Authentification (401) : Accès refusé pour un invité non authentifié.
     */
    public function test_unauthenticated_cannot_access_teachers(): void
    {
        $response = $this->getJson('/api/teachers');
        $response->assertStatus(401);
    }

    /**
     * Happy Path (201) : Création d'un enseignant par le directeur avec rémunération.
     */
    public function test_director_can_create_teacher_with_remuneration(): void
    {
        Sanctum::actingAs($this->director);

        $payload = [
            'name' => 'Prof. Anis Mahmoud',
            'subject' => 'Mathématiques',
            'phone' => '+216 98 765 432',
            'email' => 'anis.mahmoud@studycenter.com',
            'weeklyLessons' => 4,
            'remuneration_type' => 'percentage',
            'rate_percentage' => 75.0,
            'salary_per_lesson' => 35.0,
        ];

        $response = $this->postJson('/api/teachers', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'subject', 'email'])
            ->assertJson([
                'name' => 'Prof. Anis Mahmoud',
                'subject' => 'Mathématiques',
                'email' => 'anis.mahmoud@studycenter.com',
            ]);

        $this->assertDatabaseHas('teachers', [
            'name' => 'Prof. Anis Mahmoud',
            'email' => 'anis.mahmoud@studycenter.com',
            'remuneration_type' => 'percentage',
            'rate_percentage' => 75.0,
            'weekly_lessons' => 4,
        ]);
    }

    /**
     * Sécurité / RBAC : La secrétaire ne peut pas définir la rémunération financière de l'enseignant.
     */
    public function test_secretary_cannot_set_remuneration_fields(): void
    {
        Sanctum::actingAs($this->secretary);

        $payload = [
            'name' => 'Prof. Samia Triki',
            'subject' => 'Physique',
            'phone' => '+216 22 123 456',
            'email' => 'samia.triki@studycenter.com',
            'remuneration_type' => 'percentage',
            'rate_percentage' => 85.0, // Tentative de définir une rémunération
        ];

        $response = $this->postJson('/api/teachers', $payload);

        $response->assertStatus(201);

        // Les champs financiers doivent avoir été purgés (la valeur 85.0 tentée est ignorée, garde le défaut 70)
        $this->assertDatabaseHas('teachers', [
            'name' => 'Prof. Samia Triki',
            'rate_percentage' => 70,
        ]);
        $this->assertDatabaseMissing('teachers', [
            'name' => 'Prof. Samia Triki',
            'rate_percentage' => 85.0,
        ]);
    }

    /**
     * Validation (422) : Échec si champs obligatoires absents ou invalides.
     */
    public function test_create_teacher_validation_errors(): void
    {
        Sanctum::actingAs($this->director);

        $response = $this->postJson('/api/teachers', [
            'name' => '',
            'email' => 'not-an-email',
            'rate_percentage' => 150, // Dépasse 100%
            'remuneration_type' => 'type_invalide',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'rate_percentage', 'remuneration_type']);
    }

    /**
     * Happy Path (200) : Modification d'un enseignant.
     */
    public function test_can_update_teacher(): void
    {
        Sanctum::actingAs($this->director);
        $teacher = Teacher::factory()->create(['name' => 'Ancien Enseignant']);

        $response = $this->putJson("/api/teachers/{$teacher->id}", [
            'name' => 'Enseignant Mis à Jour',
            'phone' => '+216 55 444 333',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Enseignant Mis à Jour',
            ]);

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'name' => 'Enseignant Mis à Jour',
            'phone' => '+216 55 444 333',
        ]);
    }

    /**
     * Happy Path / RBAC (200) : Le directeur bascule le statut de paiement d'un enseignant (toggle-payout).
     */
    public function test_director_can_toggle_payout(): void
    {
        Sanctum::actingAs($this->director);
        $teacher = Teacher::factory()->create(['payout_status' => 'pending']);

        $response = $this->postJson("/api/teachers/{$teacher->id}/toggle-payout", [
            'status' => 'paid',
            'amount' => 450.0,
            'month' => '2026-11',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Rémunération marquée comme payée (l\'enseignant a pris son argent).',
            ]);

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'payout_status' => 'paid',
            'payout_amount' => 450.0,
            'payout_month' => '2026-11',
            'payout_by_user_id' => $this->director->id,
        ]);
    }

    /**
     * Autorisation / RBAC (403) : La secrétaire ne peut pas exécuter toggle-payout (réservé directeur).
     */
    public function test_secretary_cannot_toggle_payout(): void
    {
        Sanctum::actingAs($this->secretary);
        $teacher = Teacher::factory()->create();

        $response = $this->postJson("/api/teachers/{$teacher->id}/toggle-payout", [
            'status' => 'paid',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Autorisation / RBAC (403) : La secrétaire sans can_delete ne peut pas supprimer un enseignant.
     */
    public function test_secretary_cannot_delete_teacher_without_permission(): void
    {
        Sanctum::actingAs($this->secretary);
        $teacher = Teacher::factory()->create();

        $response = $this->deleteJson("/api/teachers/{$teacher->id}");

        $response->assertStatus(403);
    }

    /**
     * Happy Path / RBAC (200) : Le directeur supprime un enseignant (Soft Delete).
     */
    public function test_director_can_delete_teacher(): void
    {
        Sanctum::actingAs($this->director);
        $teacher = Teacher::factory()->create();

        $response = $this->deleteJson("/api/teachers/{$teacher->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
    }
}
