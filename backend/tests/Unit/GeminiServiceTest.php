<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;
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

    /**
     * Test que le Function Calling Gemini 'ajouter_eleve' insère l'élève en base,
     * enregistre l'ActivityLog et renvoie le functionResponse à Gemini.
     */
    public function test_function_calling_adds_student_when_authorized(): void
    {
        $director = User::create([
            'name' => 'Directeur Général',
            'email' => 'directeur@studycenter.com',
            'password' => bcrypt('secret123'),
            'role' => 'director',
            'is_active' => true,
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                // 1er appel : Gemini renvoie un functionCall pour ajouter l'élève
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'role' => 'model',
                                'parts' => [
                                    [
                                        'functionCall' => [
                                            'id' => 'call_eleve_123',
                                            'name' => 'ajouter_eleve',
                                            'args' => [
                                                'nom' => 'Ben Ali',
                                                'prenom' => 'Mohamed',
                                                'niveau' => 'Bac Mathématiques',
                                                'telephone_parent' => '98123456',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200)
                // 2ème appel : Gemini reçoit la functionResponse et formule la confirmation
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'role' => 'model',
                                'parts' => [
                                    [
                                        'text' => "✅ L'élève Mohamed Ben Ali a été inscrit avec succès en Bac Mathématiques !",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $response = $this->service->chat($director, "Inscris l'élève Mohamed Ben Ali en Bac Mathématiques avec tél parent 98123456");

        // 1. Vérifier la réponse finale
        $this->assertEquals('success', $response['status']);
        $this->assertFalse($response['is_fallback']);
        $this->assertStringContainsString('Mohamed Ben Ali', $response['reply']);

        // 2. Vérifier l'insertion de l'élève en base de données
        $student = Student::where('name', 'Mohamed Ben Ali')->first();
        $this->assertNotNull($student);
        $this->assertEquals('Bac Mathématiques', $student->level);
        $this->assertEquals('98123456', $student->parent_phone);
        $this->assertEquals('98123456', $student->phone);
        $this->assertEquals('active', $student->status);

        // 3. Vérifier l'enregistrement dans le journal d'activité
        $this->assertDatabaseHas('activity_logs', [
            'user_name' => 'Directeur Général',
            'type'      => 'student',
            'action'    => 'created',
        ]);

        // 4. Vérifier que 2 requêtes HTTP ont bien été émises (1ère avec tools, 2ème avec functionResponse)
        Http::assertSentCount(2);

        // 5. Vérifier que la 2ème requête contenait le functionResponse avec le résultat
        Http::assertSent(function ($request) {
            $data = $request->data();
            $contents = $data['contents'] ?? [];
            foreach ($contents as $turn) {
                if (($turn['role'] ?? '') === 'function') {
                    $funcResp = $turn['parts'][0]['functionResponse'] ?? null;
                    return $funcResp !== null &&
                        $funcResp['name'] === 'ajouter_eleve' &&
                        ($funcResp['response']['status'] ?? '') === 'success';
                }
            }
            return false;
        });
    }

    /**
     * Test qu'un utilisateur non autorisé (ex: Enseignant) est bloqué lors de l'appel de fonction.
     */
    public function test_function_calling_denied_for_unauthorized_user(): void
    {
        $teacher = User::create([
            'name' => 'Professeur Ahmed',
            'email' => 'prof.ahmed@studycenter.com',
            'password' => bcrypt('secret123'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                // 1er appel : Gemini tente d'appeler la fonction
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'role' => 'model',
                                'parts' => [
                                    [
                                        'functionCall' => [
                                            'name' => 'ajouter_eleve',
                                            'args' => [
                                                'nom' => 'Trabelsi',
                                                'prenom' => 'Karim',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200)
                // 2ème appel : Gemini reçoit l'erreur de permission et avertit l'utilisateur
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'role' => 'model',
                                'parts' => [
                                    [
                                        'text' => "Désolé, seuls le directeur et le secrétariat sont autorisés à inscrire des élèves.",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $response = $this->service->chat($teacher, "Ajoute l'élève Karim Trabelsi");

        // 1. Vérifier qu'aucun élève n'a été inséré
        $this->assertDatabaseMissing('students', [
            'name' => 'Karim Trabelsi',
        ]);

        // 2. Vérifier que la functionResponse envoyée à Gemini comportait le statut 'error'
        Http::assertSent(function ($request) {
            $data = $request->data();
            $contents = $data['contents'] ?? [];
            foreach ($contents as $turn) {
                if (($turn['role'] ?? '') === 'function') {
                    $funcResp = $turn['parts'][0]['functionResponse'] ?? null;
                    return $funcResp !== null &&
                        ($funcResp['response']['status'] ?? '') === 'error';
                }
            }
            return false;
        });
    }
}
