<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UsersApiTest extends TestCase
{
    use RefreshDatabase;

    private User $director;
    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::factory()->create([
            'name' => 'Directeur Général',
            'email' => 'directeur@studycenter.com',
            'role' => 'director',
            'is_active' => true,
        ]);

        $this->secretary = User::factory()->create([
            'name' => 'Secrétaire Accueil',
            'email' => 'secretaire@studycenter.com',
            'role' => 'secretary',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
    }

    /**
     * Happy Path (200) : Consultation du profil utilisateur connecté.
     */
    public function test_user_can_view_own_profile(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'role',
                'permissions',
            ])
            ->assertJson([
                'id' => $this->secretary->id,
                'email' => 'secretaire@studycenter.com',
            ]);
    }

    /**
     * Happy Path (200) : Modification de son propre profil.
     */
    public function test_user_can_update_own_profile(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->putJson('/api/profile', [
            'name' => 'Secrétaire Responsable',
            'email' => 'secretaire.new@studycenter.com',
            'phone' => '+216 20 999 888',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profil mis à jour avec succès.',
                'user' => [
                    'name' => 'Secrétaire Responsable',
                    'email' => 'secretaire.new@studycenter.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->secretary->id,
            'email' => 'secretaire.new@studycenter.com',
        ]);
    }

    /**
     * Happy Path (200) : Modification de son propre mot de passe.
     */
    public function test_user_can_update_password(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'password123',
            'new_password' => 'nouveaumdp123',
            'new_password_confirmation' => 'nouveaumdp123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Votre mot de passe a été modifié avec succès.'
            ]);

        // Vérification de la mise à jour effective du mot de passe
        $this->secretary->refresh();
        $this->assertTrue(Hash::check('nouveaumdp123', $this->secretary->password));
    }

    /**
     * Validation (422) : Échec de changement de mot de passe avec un mot de passe actuel erroné.
     */
    public function test_update_password_fails_with_wrong_current_password(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'mauvais_mdp_actuel',
            'new_password' => 'nouveaumdp123',
            'new_password_confirmation' => 'nouveaumdp123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /**
     * Autorisation / RBAC (403) : La secrétaire ne peut pas accéder à la liste des utilisateurs du centre (/api/users).
     */
    public function test_secretary_cannot_access_user_management(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    /**
     * Happy Path / RBAC (200) : Le directeur peut lister les utilisateurs du centre.
     */
    public function test_director_can_list_users(): void
    {
        Sanctum::actingAs($this->director);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(2);
    }

    /**
     * Happy Path / RBAC (201) : Le directeur crée un nouveau compte utilisateur.
     */
    public function test_director_can_create_user(): void
    {
        Sanctum::actingAs($this->director);

        $payload = [
            'name' => 'Nouvelle Secrétaire',
            'email' => 'secretaire2@studycenter.com',
            'phone' => '+216 20 111 333',
            'password' => 'motdepasse123',
            'role' => 'secretary',
            'permissions' => ['students', 'attendance'],
        ];

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Compte personnel créé avec succès.',
                'user' => [
                    'name' => 'Nouvelle Secrétaire',
                    'email' => 'secretaire2@studycenter.com',
                    'role' => 'secretary',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'secretaire2@studycenter.com',
            'role' => 'secretary',
        ]);
    }

    /**
     * Authentification (401) : Accès refusé pour invité.
     */
    public function test_unauthenticated_cannot_access_users(): void
    {
        $response = $this->getJson('/api/profile');
        $response->assertStatus(401);
    }
}
