<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Happy Path : Connexion réussie avec identifiants valides.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'directeur@studycenter.com',
            'password' => Hash::make('secret1234'),
            'role' => 'director',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'directeur@studycenter.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'user' => [
                    'id',
                    'name',
                    'role',
                    'email',
                    'permissions',
                ],
                'token',
                'expires_in',
                'login_time',
            ])
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'email' => 'directeur@studycenter.com',
                    'role' => 'director',
                ],
            ]);

        // Vérification de la création du log d'activité de connexion
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);
    }

    /**
     * Authentification (401) : Échec de connexion avec mot de passe erroné.
     */
    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'user@studycenter.com',
            'password' => Hash::make('secret1234'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@studycenter.com',
            'password' => 'mauvais_mdp',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Adresse email ou mot de passe incorrect.',
            ]);
    }

    /**
     * Authentification (401) : Échec avec utilisateur inexistant.
     */
    public function test_login_fails_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'inexistant@studycenter.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Autorisation / Sécurité (403) : Compte désactivé par la direction.
     */
    public function test_deactivated_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'desactive@studycenter.com',
            'password' => Hash::make('secret1234'),
            'role' => 'secretary',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'desactive@studycenter.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Ce compte a été désactivé par la direction. Veuillez contacter le Directeur.',
            ]);
    }

    /**
     * Happy Path : Déconnexion réussie pour un utilisateur authentifié.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Déconnecté avec succès',
            ]);
    }

    /**
     * Authentification (401) : Déconnexion refusée pour un invité non authentifié.
     */
    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }
}
