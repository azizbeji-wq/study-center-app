<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantValidationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur in-memory pour tester avec Sanctum
        $this->user = new User([
            'name' => 'Professeur Anis',
            'email' => 'anis@studycenter.com',
            'role' => 'teacher',
        ]);
        $this->user->id = 1;
        $this->user->setRelation('teacher', null);
    }

    /**
     * Test que l'accès non authentifié renvoie 401.
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/api/ai/ask', [
            'message' => 'Bonjour',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test que le champ 'message' dépassant 1000 caractères est rejeté avec un code 422.
     */
    public function test_message_exceeding_1000_characters_is_rejected(): void
    {
        $tooLongMessage = str_repeat('a', 1001);

        $response = $this->actingAs($this->user)
            ->postJson('/api/ai/ask', [
                'message' => $tooLongMessage,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
        $response->assertJsonFragment([
            'message' => ['Le message ne peut pas dépasser 1000 caractères.']
        ]);
    }

    /**
     * Test que le tableau 'history' dépassant 10 éléments est rejeté avec un code 422.
     */
    public function test_history_exceeding_10_elements_is_rejected(): void
    {
        $elevenHistoryItems = [];
        for ($i = 1; $i <= 11; $i++) {
            $elevenHistoryItems[] = [
                'sender' => 'user',
                'text' => "Message historique {$i}",
            ];
        }

        $response = $this->actingAs($this->user)
            ->postJson('/api/ai/ask', [
                'message' => 'Bonjour assistant',
                'history' => $elevenHistoryItems,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['history']);
        $response->assertJsonFragment([
            'history' => ["L'historique ne peut pas dépasser 10 éléments."]
        ]);
    }

    /**
     * Test qu'une requête valide avec historique <= 10 et message <= 1000 est acceptée.
     */
    public function test_valid_request_passes_validation(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Excellente question ! Voici la réponse.']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $validHistory = [];
        for ($i = 1; $i <= 5; $i++) {
            $validHistory[] = [
                'sender' => $i % 2 === 0 ? 'ai' : 'user',
                'text' => "Message {$i}",
            ];
        }

        $response = $this->actingAs($this->user)
            ->postJson('/api/ai/ask', [
                'message' => 'Comment préparer une séance de 2h ?',
                'history' => $validHistory,
                'context' => [
                    'subject' => 'Mathématiques',
                    'level' => 'Baccalauréat',
                ]
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'reply']);
    }
}
