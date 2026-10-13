<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    private GeminiService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeminiService();
        $this->user = new User([
            'name' => 'Professeur Test',
            'email' => 'prof@test.com',
            'role' => 'teacher',
        ]);
        Config::set('services.gemini.api_key', 'test-secret-key-12345');
        Config::set('services.gemini.model', 'gemini-2.5-flash');
        Config::set('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    }

    /**
     * Test que la clé d'API est transmise UNIQUEMENT dans l'en-tête 'x-goog-api-key'
     * et n'est JAMAIS injectée dans l'URL (?key=...).
     */
    public function test_api_key_sent_in_header_and_absent_from_url(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Bonjour, comment puis-je vous aider ?']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->service->chat($this->user, 'Bonjour');

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Bonjour, comment puis-je vous aider ?', $response['reply']);

        Http::assertSent(function ($request) {
            // 1. Vérifier la présence du header HTTP 'x-goog-api-key'
            $hasKeyHeader = $request->hasHeader('x-goog-api-key') &&
                $request->header('x-goog-api-key')[0] === 'test-secret-key-12345';

            // 2. Vérifier l'ABSENCE STRICTE de la clé dans l'URL query string
            $urlDoesNotContainKey = !str_contains($request->url(), 'key=');

            return $hasKeyHeader && $urlDoesNotContainKey;
        });
    }

    /**
     * Test que les blocs catch et erreurs ne font JAMAIS fuiter $e->getMessage() ni l'URL au client.
     */
    public function test_catch_block_masks_internal_error_message(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function () {
                throw new \Exception('DB_PASSWORD=supersecret host=internal.database.local');
            }
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($msg) {
                return str_contains($msg, 'Erreur connexion Gemini');
            });

        $response = $this->service->chat($this->user, 'Créer un plan');

        $this->assertEquals('error', $response['status']);
        $this->assertEquals("Le service d'assistance est momentanément indisponible.", $response['reply']);

        // Vérifier qu'aucune bribe de message technique ou d'URL sensible ne fuite
        $jsonString = json_encode($response);
        $this->assertStringNotContainsString('supersecret', $jsonString);
        $this->assertStringNotContainsString('internal.database.local', $jsonString);
        $this->assertStringNotContainsString('generativelanguage.googleapis.com', $jsonString);
    }

    /**
     * Test que le tuteur local répond avec la procédure exacte pour "ajouter un élève".
     */
    public function test_fallback_answers_student_question_in_french_and_derja(): void
    {
        Config::set('services.gemini.api_key', '');

        // 1. En français
        $resFr = $this->service->chat($this->user, 'ajouter un élève');
        $this->assertEquals('success', $resFr['status']);
        $this->assertTrue($resFr['is_fallback']);
        $this->assertStringContainsString('Ajouter un élève', $resFr['reply']);
        $this->assertStringContainsString('Inscrire un nouvel élève', $resFr['reply']);

        // 2. En derja tunisienne
        $resTn = $this->service->chat($this->user, 'kifech nzid tilmidh ?');
        $this->assertEquals('success', $resTn['status']);
        $this->assertTrue($resTn['is_fallback']);
        $this->assertStringContainsString('Ajouter un élève', $resTn['reply']);
    }
}
