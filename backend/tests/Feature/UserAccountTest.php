<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $director;
    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        // Création du directeur fondateur (ID 1)
        $this->director = User::create([
            'name' => 'Directeur Fondateur',
            'email' => 'director@studycenter.com',
            'password' => Hash::make('director123'),
            'role' => 'director',
            'is_active' => true,
        ]);

        // Création d'une secrétaire (ID 2)
        $this->secretary = User::create([
            'name' => 'Secrétaire Principale',
            'email' => 'secretary@studycenter.com',
            'password' => Hash::make('secretary123'),
            'role' => 'secretary',
            'is_active' => true,
        ]);
    }

    public function test_user_can_view_own_profile(): void
    {
        $response = $this->actingAs($this->secretary)->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->secretary->id,
                'name' => 'Secrétaire Principale',
                'email' => 'secretary@studycenter.com',
                'role' => 'secretary',
            ]);
    }

    public function test_user_can_update_own_profile(): void
    {
        $response = $this->actingAs($this->secretary)->putJson('/api/profile', [
            'name' => 'Secrétaire Modifiée',
            'email' => 'nouvelle.secretaire@studycenter.com',
            'phone' => '+216 99 888 777',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.name', 'Secrétaire Modifiée')
            ->assertJsonPath('user.email', 'nouvelle.secretaire@studycenter.com')
            ->assertJsonPath('user.phone', '+216 99 888 777');

        $this->assertDatabaseHas('users', [
            'id' => $this->secretary->id,
            'name' => 'Secrétaire Modifiée',
            'email' => 'nouvelle.secretaire@studycenter.com',
        ]);
    }

    public function test_user_can_change_own_password(): void
    {
        $response = $this->actingAs($this->secretary)->putJson('/api/profile/password', [
            'current_password' => 'secretary123',
            'new_password' => 'newSecretPass456',
            'new_password_confirmation' => 'newSecretPass456',
        ]);

        $response->assertStatus(200);

        $this->secretary->refresh();
        $this->assertTrue(Hash::check('newSecretPass456', $this->secretary->password));
    }

    public function test_password_change_fails_if_current_password_is_wrong(): void
    {
        $response = $this->actingAs($this->secretary)->putJson('/api/profile/password', [
            'current_password' => 'mauvais_pass',
            'new_password' => 'newSecretPass456',
            'new_password_confirmation' => 'newSecretPass456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_secretary_cannot_access_user_management(): void
    {
        // Lister les utilisateurs
        $this->actingAs($this->secretary)->getJson('/api/users')
            ->assertStatus(403);

        // Créer un utilisateur
        $this->actingAs($this->secretary)->postJson('/api/users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => '123456',
            'role' => 'secretary',
        ])->assertStatus(403);

        // Supprimer un utilisateur
        $this->actingAs($this->secretary)->deleteJson("/api/users/{$this->director->id}")
            ->assertStatus(403);
    }

    public function test_director_can_list_users(): void
    {
        $response = $this->actingAs($this->director)->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_director_can_create_secretary_account(): void
    {
        $response = $this->actingAs($this->director)->postJson('/api/users', [
            'name' => 'Nouvelle Secrétaire Matin',
            'email' => 'matin@studycenter.com',
            'phone' => '+216 22 111 333',
            'password' => 'matin123',
            'role' => 'secretary',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.name', 'Nouvelle Secrétaire Matin')
            ->assertJsonPath('user.role', 'secretary');

        $this->assertDatabaseHas('users', [
            'email' => 'matin@studycenter.com',
            'role' => 'secretary',
        ]);
    }

    public function test_director_can_reset_secretary_password(): void
    {
        $response = $this->actingAs($this->director)->postJson("/api/users/{$this->secretary->id}/reset-password", [
            'password' => 'nouveauPassReinit123',
        ]);

        $response->assertStatus(200);

        $this->secretary->refresh();
        $this->assertTrue(Hash::check('nouveauPassReinit123', $this->secretary->password));
    }

    public function test_director_cannot_delete_himself(): void
    {
        $response = $this->actingAs($this->director)->deleteJson("/api/users/{$this->director->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Sécurité : Vous ne pouvez pas supprimer votre propre compte actuellement connecté.');
    }

    public function test_founder_director_is_protected_from_deletion_and_downgrade(): void
    {
        // Créer un second directeur
        $secondDirector = User::create([
            'name' => 'Directeur Adjoint',
            'email' => 'adjoint@studycenter.com',
            'password' => Hash::make('pass123'),
            'role' => 'director',
            'is_active' => true,
        ]);

        // Le second directeur tente de supprimer le fondateur (ID 1)
        $response = $this->actingAs($secondDirector)->deleteJson("/api/users/1");
        $response->assertStatus(403);

        // Tenter de rétrograder le fondateur en secrétaire
        $response = $this->actingAs($secondDirector)->putJson("/api/users/1", [
            'name' => 'Directeur Fondateur',
            'email' => 'director@studycenter.com',
            'role' => 'secretary',
        ]);
        $response->assertStatus(422);
    }
}
