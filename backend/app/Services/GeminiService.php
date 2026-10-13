<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /**
     * Génère une réponse via l'API Google Gemini avec contexte de rôle et consignes strictes (Guardrails).
     */
    public function chat(User $user, string $message, array $history = [], array $context = []): array
    {
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        $systemPrompt = $this->buildSystemInstruction($user, $context);

        // Si aucune clé d'API n'est configurée, utiliser le moteur de secours intelligent
        if (empty($apiKey) || $apiKey === 'your_gemini_api_key_here') {
            return [
                'status' => 'success',
                'reply' => $this->generateFallbackResponse($user, $message, $context),
                'model' => 'local-smart-assistant',
                'is_fallback' => true,
                'notice' => 'Clé GEMINI_API_KEY non configurée dans .env. Réponse générée par le tuteur local Study Center.',
            ];
        }

        // Préparation du payload Gemini
        $contents = $this->buildContents($history, $message);

        $url = "{$baseUrl}/models/{$model}:generateContent";

        try {
            $tools = $this->buildTools();

            $payload = [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => $contents,
                'tools' => $tools,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048,
                ],
            ];

            $response = Http::timeout(25)
                ->withHeaders([
                    'Content-Type'   => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $candidate = $data['candidates'][0] ?? null;
                $parts = $candidate['content']['parts'] ?? [];

                // 1. Détection d'un functionCall éventuel
                $functionCallPart = null;
                foreach ($parts as $part) {
                    if (isset($part['functionCall'])) {
                        $functionCallPart = $part['functionCall'];
                        break;
                    }
                }

                if ($functionCallPart !== null) {
                    $funcName = $functionCallPart['name'] ?? '';
                    $funcArgs = $functionCallPart['args'] ?? [];
                    $funcId = $functionCallPart['id'] ?? null;

                    // Exécution locale de l'action demandée
                    $functionResult = $this->executeFunctionCall($user, $funcName, $funcArgs);

                    // Préparation du 2nd tour (renvoi de la functionResponse à Gemini pour formulation finale)
                    $secondContents = $contents;
                    $secondContents[] = $candidate['content'] ?? [
                        'role' => 'model',
                        'parts' => [
                            ['functionCall' => $functionCallPart],
                        ],
                    ];

                    $funcResponsePart = [
                        'name' => $funcName,
                        'response' => $functionResult,
                    ];
                    if (!empty($funcId)) {
                        $funcResponsePart['id'] = $funcId;
                    }

                    $secondContents[] = [
                        'role' => 'function',
                        'parts' => [
                            ['functionResponse' => $funcResponsePart],
                        ],
                    ];

                    $secondPayload = [
                        'systemInstruction' => [
                            'parts' => [
                                ['text' => $systemPrompt],
                            ],
                        ],
                        'contents' => $secondContents,
                        'tools' => $tools,
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'topK' => 40,
                            'topP' => 0.95,
                            'maxOutputTokens' => 2048,
                        ],
                    ];

                    $secondResponse = Http::timeout(25)
                        ->withHeaders([
                            'Content-Type'   => 'application/json',
                            'x-goog-api-key' => $apiKey,
                        ])
                        ->post($url, $secondPayload);

                    if ($secondResponse->successful()) {
                        $secondData = $secondResponse->json();
                        $secondParts = $secondData['candidates'][0]['content']['parts'] ?? [];
                        $secondText = '';
                        foreach ($secondParts as $sp) {
                            if (isset($sp['text'])) {
                                $secondText .= $sp['text'];
                            }
                        }

                        if (!empty(trim($secondText))) {
                            return [
                                'status' => 'success',
                                'reply' => trim($secondText),
                                'model' => $model,
                                'is_fallback' => false,
                            ];
                        }
                    }

                    // En cas d'indisponibilité du second tour mais où l'action a réussi
                    if ($functionResult['status'] === 'success') {
                        $nomEleve = $functionResult['student']['nom_complet'] ?? '';
                        return [
                            'status' => 'success',
                            'reply' => "✅ L'élève **{$nomEleve}** a bien été inscrit(e) dans Study Center !",
                            'model' => $model,
                            'is_fallback' => false,
                        ];
                    }

                    return [
                        'status' => 'success',
                        'reply' => $functionResult['message'] ?? "L'opération a été traitée.",
                        'model' => $model,
                        'is_fallback' => false,
                    ];
                }

                // 2. Réponse textuelle directe
                $replyText = '';
                foreach ($parts as $p) {
                    if (isset($p['text'])) {
                        $replyText .= $p['text'];
                    }
                }

                if (!empty(trim($replyText))) {
                    return [
                        'status' => 'success',
                        'reply' => trim($replyText),
                        'model' => $model,
                        'is_fallback' => false,
                    ];
                }
            }

            // En cas d'erreur API (quota, clé invalide, etc.)
            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body();
            Log::error("Gemini API Error (HTTP {$response->status()}, Model {$model}): " . $errorMessage);

            // Secours gracieux sans exposer le message technique de l'API externe
            return [
                'status' => 'success',
                'reply' => $this->generateFallbackResponse($user, $message, $context),
                'model' => 'local-smart-assistant',
                'is_fallback' => true,
                'notice' => "Le service d'assistance externe est momentanément indisponible (HTTP {$response->status()}). Réponse générée par le tuteur local Study Center.",
            ];

        } catch (\Throwable $e) {
            // Log de l'exception sans jamais exposer $e->getMessage() ni l'URL au client
            Log::error("Erreur connexion Gemini (Model {$model}): " . $e->getMessage());

            return [
                'status' => 'error',
                'reply' => "Le service d'assistance est momentanément indisponible.",
                'model' => 'fallback-assisted',
                'is_fallback' => true,
                'message' => "Le service d'assistance est momentanément indisponible.",
                'notice' => "Erreur de connexion au service Gemini.",
            ];
        }
    }

    /**
     * Construit le prompt système (System Instruction) avec rôles, fonctionnalités de l'app et garde-fous stricts.
     */
    private function buildSystemInstruction(User $user, array $context): string
    {
        $role = $user->role ?? 'secretary';
        $roleLabel = match ($role) {
            'director' => 'Directeur / Direction Générale',
            'teacher' => 'Enseignant / Professeur',
            default => 'Personnel Administratif / Secrétaire',
        };

        $teacherInfo = '';
        if ($role === 'teacher' && $user->teacher) {
            $t = $user->teacher;
            $teacherInfo = "\n- Matière(s) de l'enseignant : {$t->subject}\n- Séances hebdo : {$t->weekly_lessons}\n- Téléphone : {$t->phone}";
        }

        $extraContext = '';
        if (!empty($context['subject'])) {
            $extraContext .= "\n- Matière en cours : {$context['subject']}";
        }
        if (!empty($context['level'])) {
            $extraContext .= "\n- Niveau scolaire : {$context['level']}";
        }

        // Posture spécifique au rôle de l'utilisateur
        $rolePosture = match ($role) {
            'director' => <<<DIR
### POSTURE DU MODÈLE : CONSEILLER STRATÉGIQUE, FINANCIER & DE GESTION DE DIRECTION
Tu es le bras droit analytique et conseiller du DIRECTEUR de Study Center.
- **Missions prioritaires** :
  1. **Pilotage Financier & Rentabilité** : Analyse du chiffre d'affaires, marge nette du centre, frais fixes/variables, clôture mensuelle des comptes.
  2. **Rémunérations Enseignants (3 modes)** :
     - Formule A (`fixed_center`) : Part fixe convenue prélevée par élève (ex: 20 TND/élève/mois), reliquat reversé au professeur.
     - Formule B (`fixed`) : Tarif fixe par élève présent (ex: 15 TND) ou forfait par cours effectué.
     - Formule C (`percentage`) : Pourcentage conventionné (ex: 70% prof / 30% centre).
     - RÈGLE STRICTE NON-NÉGOCIABLE : L'administration absorbe 100% des remises accordées aux élèves. Le salaire prof est obligatoirement calculé sur le prix plein avant remise.
  3. **Supervision Opérationnelle** : Analyse des effectifs, détection des retards de paiement, optimisation des plannings de salles et gestion des comptes utilisateurs/permissions.
  4. **Gestion des Élèves** : Le directeur a accès à l'onglet « Élèves » pour inscrire un nouvel élève, consulter et modifier les fiches élèves, et gérer leurs affectations aux groupes.
DIR,
            'teacher' => <<<TEA
### POSTURE DU MODÈLE : COPILOTE PÉDAGOGIQUE EXPERT DU SYSTÈME ÉDUCATIF TUNISIEN
Tu es le tuteur et copilote pédagogique de l'ENSEIGNANT de Study Center.
- **Missions prioritaires** :
  1. **Conception de Séances (2h)** : Structuration pédagogique conforme aux programmes tunisiens (Accueil 15 min, Notion clé 40 min, Pratique dirigée 50 min, Synthèse 15 min).
  2. **Génération d'Exercices & Évaluations** : Élaboration de séries progressives (Application directe, Approfondissement, Synthèse type Examen / Devoir de contrôle ou synthèse / Baccalauréat tunisien).
  3. **Gestion de Classe & Suivi** : Prise des présences en ligne, gestion des retards/absences, communication professionnelle et bienveillante avec les parents d'élèves (SMS/WhatsApp).
  4. **Emploi du temps & Groupes** : Consultation des créneaux, attribution des salles et suivi des effectifs.
TEA,
            default => <<<SEC
### POSTURE DU MODÈLE : ASSISTANTE ADMINISTRATIVE & RELATION CLIENTÈLE/FAMILLES
Tu es la conseillère et assistante administrative de la SECRÉTAIRE / DU PERSONNEL de Study Center.
- **Missions prioritaires** :
  1. **Accueil & Inscriptions** : Enregistrement des nouveaux élèves, affectation aux groupes, suivi des coordonnées parents.
  2. **Encaissements & Caisse** : Facturation des séances et forfaits mensuels, émission des reçus de paiement, gestion des impayés et suivi des remises autorisées.
  3. **Communication Parents** : Rédaction de messages WhatsApp/SMS bienveillants et clairs pour rappeler une échéance de paiement, signaler une absence imprévue ou confirmer une inscription.
  4. **Garderie Parascolaire** : Pointage journalier des arrivées/départs des enfants, suivi des forfaits cantine et parascolaire.
  5. **Coordination des Salles** : Planning hebdomadaire et détection des chevauchements d'horaires.
SEC
        };

        return <<<PROMPT
Tu es le copilote et assistant IA officiel de l'application « Study Center App » (Centre d'Études de soutien scolaire et parascolaire en Tunisie).

### IDENTITÉ DE L'UTILISATEUR CONNECTÉ :
- Nom : {$user->name}
- Rôle : {$roleLabel} ({$role})
- Email : {$user->email}{$teacherInfo}{$extraContext}

{$rolePosture}

### RÈGLE LINGUISTIQUE ESSENTIELLE :
- Détecte automatiquement la langue ou le dialecte utilisé par l'utilisateur.
- Si l'utilisateur s'adresse à toi en FRANÇAIS, réponds en français soigné, professionnel, encourageant et concis.
- Si l'utilisateur s'adresse à toi en DÉRJA TUNISIENNE (Arabe dialectal tunisien en caractères latins 'arabizi' ou arabes), RÉPONDS OBLIGATOIREMENT EN DÉRJA TUNISIENNE authentique, chaleureuse et naturelle (exemples: "3asslema", "Ahla bik", "Kifech n3awnek", "Tnajjem temchi l onglet...", "Famma bouton...").
- Tu peux mixer le français et la derja comme c'est l'usage courant en Tunisie chez les professionnels de l'éducation.

### GARDE-FOUS STRICTS (GUARDRAILS) - NON NÉGOCIABLE :
- Tu dois STRICTEMENT REFUSER tout sujet qui sort du cadre de l'application Study Center, de l'éducation, des cours, des devoirs, des plannings, des présences, des finances de cours ou de la pédagogie scolaire tunisienne.
- Sujets INTERDITS : politique générale, polémiques, jeux vidéo, célébrités, crypto-monnaies, piratage, recettes de cuisine sans rapport avec l'école, conseils médicaux hors premiers soins scolaires, astrologie, etc.
- Réponse en cas de question hors sujet :
  * En français : « Je suis exclusivement dédié à l'assistance du Centre d'Études (Study Center). Je ne peux répondre qu'aux questions relatives à vos cours, plannings, présences, finances du centre ou à la gestion scolaire. »
  * En derja tunisienne : « Ena l'assistant mte3 Study Center khw. Njewbek ken f 7ajet teb3a el 9raya, les cours, l'emploi du temps, el 7oudhour, les élèves w gestion mte3 el centre. »

### CONNAISSANCES APPROFONDIES DE L'APPLICATION « STUDY CENTER APP » :
1. **Emploi du temps & Salles** : Planning hebdomadaire par jour (Lundi à Dimanche), gestion des salles (ex: Salle 1, Salle 2) avec détection bloquante des conflits de salle, et avertissement pour conflit de groupe.
2. **Présences & Appel** : Pointage par séance en 1 clic (Présent [vert], Absent [rouge], En retard [orange], Excusé [bleu]), calcul automatique du taux d'assiduité et facturation des séances réelles.
3. **Finances & Rémunération Enseignants** :
   - Formule A (fixed_center) : Part fixe centre par élève (ex: 20 TND/élève) — le prof prend le reliquat.
   - Formule B (fixed) : Tarif fixe par élève inscrit ou forfait par séance pour l'enseignant.
   - Formule C (percentage) : Pourcentage sur le montant des cours (ex: 70% prof / 30% centre).
   - Les remises accordées aux élèves sont intégralement prises en charge par la direction du centre.
4. **Garderie Parascolaire** : Pointage journalier et forfaits mensuels pour les enfants accueillis.
5. **Comptabilité & Bilan** : Réservé au directeur (recettes, dépenses fixes et variables, clôture mensuelle).

### OUTILS DISPONIBLES ET RÈGLES DE FUNCTION CALLING :
Tu disposes d'un outil `ajouter_eleve` pour inscrire un nouvel élève directement dans la base de données de Study Center :
- Paramètres : `nom` (obligatoire), `prenom` (obligatoire), `niveau` (optionnel), `telephone_parent` (optionnel).
- RÈGLE ESSENTIELLE : Si l'utilisateur demande d'ajouter ou d'inscrire un élève mais ne fournit pas encore son nom et son prénom, NE DÉCLENCHE PAS l'outil `ajouter_eleve`. Demande-lui poliment de préciser le prénom, le nom, ainsi que le niveau scolaire (ex: Bac, Lycée, Collège) et le numéro de téléphone des parents avant de procéder.
- N'appelle `ajouter_eleve` que lorsque l'utilisateur t'a communiqué au minimum le prénom et le nom de l'élève.
- Dès que tu reçois la `functionResponse` confirmant l'inscription, confirme à l'utilisateur avec bienveillance, clarté et professionnalisme (en reprenant le nom complet, le niveau et le numéro parent) en français ou en derja selon la langue de la conversation.
- Si le résultat de la fonction indique une erreur de permission (accès refusé), explique courtoisement que seuls le directeur et le secrétariat autorisé ont le droit d'inscrire des élèves.

Sois toujours structuré, courtois, encourageant et concis. Utilise des puces et des mises en gras pour aérer tes réponses.
PROMPT;
    }

    /**
     * Formate l'historique et le message pour l'API Gemini.
     */
    private function buildContents(array $history, string $newMessage): array
    {
        $contents = [];

        // Ne conserver que les 10 derniers échanges pour garder une mémoire fluide
        $recentHistory = array_slice($history, -10);

        foreach ($recentHistory as $item) {
            $role = ($item['role'] ?? ($item['sender'] === 'ai' ? 'model' : 'user')) === 'model' ? 'model' : 'user';
            $text = trim($item['text'] ?? $item['content'] ?? '');

            if ($text !== '') {
                $contents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $text],
                    ],
                ];
            }
        }

        // Ajouter le nouveau message de l'utilisateur
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $newMessage],
            ],
        ];

        return $contents;
    }

    /**
     * Déclaration des outils (Function Calling) disponibles pour Gemini.
     */
    private function buildTools(): array
    {
        return [
            [
                'functionDeclarations' => [
                    [
                        'name' => 'ajouter_eleve',
                        'description' => "Inscrire un nouvel élève dans la base de données de Study Center. Cette fonction ne doit être déclenchée que lorsque l'utilisateur a fourni au minimum le prénom et le nom de l'élève.",
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'nom' => [
                                    'type' => 'STRING',
                                    'description' => "Nom de famille de l'élève (ex: Ben Ali, Trabelsi).",
                                ],
                                'prenom' => [
                                    'type' => 'STRING',
                                    'description' => "Prénom de l'élève (ex: Mohamed, Yassine, Nour).",
                                ],
                                'niveau' => [
                                    'type' => 'STRING',
                                    'description' => "Niveau scolaire officiel tunisien de l'élève (ex: Bac Mathématiques, 3ème Sciences, 9ème Année de base, 6ème Primaire).",
                                ],
                                'telephone_parent' => [
                                    'type' => 'STRING',
                                    'description' => "Numéro de téléphone du parent ou tuteur pour les notifications et reçus (ex: 98123456 ou 21345678).",
                                ],
                            ],
                            'required' => ['nom', 'prenom'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Exécute une fonction appelée par Gemini (Function Calling).
     */
    private function executeFunctionCall(User $user, string $functionName, array $args): array
    {
        if ($functionName === 'ajouter_eleve') {
            // Vérification des permissions : uniquement 'director' ou 'secretary' avec la permission 'students'
            $isRoleAllowed = in_array($user->role, ['director', 'secretary'], true);
            $hasPermission = method_exists($user, 'hasPermission') ? $user->hasPermission('students') : ($user->role === 'director');

            if (!$isRoleAllowed || !$hasPermission) {
                return [
                    'status' => 'error',
                    'message' => "Accès refusé : Seuls les utilisateurs avec le rôle Directeur ou Secrétaire autorisés peuvent inscrire des élèves.",
                ];
            }

            $nom = trim($args['nom'] ?? $args['last_name'] ?? '');
            $prenom = trim($args['prenom'] ?? $args['first_name'] ?? '');

            if (empty($nom) || empty($prenom)) {
                return [
                    'status' => 'error',
                    'message' => "Le prénom et le nom de l'élève sont obligatoires pour valider l'inscription.",
                ];
            }

            $fullName = trim($prenom . ' ' . $nom);
            $niveau = !empty($args['niveau']) ? trim($args['niveau']) : 'Non spécifié';
            $parentPhone = !empty($args['telephone_parent']) ? trim($args['telephone_parent']) : null;
            $phone = $parentPhone ?: 'Non renseigné';

            $student = Student::create([
                'name' => $fullName,
                'level' => $niveau,
                'phone' => $phone,
                'parent_phone' => $parentPhone,
                'status' => 'active',
                'is_remise' => false,
                'remise' => 0,
                'discount_type' => 'fixed',
                'base_fee' => 0,
            ]);

            ActivityLog::record(
                $user,
                'student',
                'created',
                "Inscription de l'élève {$student->name} par l'assistant IA Gemini",
                [
                    'student_id'   => $student->id,
                    'name'         => $student->name,
                    'level'        => $student->level,
                    'parent_phone' => $student->parent_phone,
                    'channel'      => 'gemini_function_calling',
                ]
            );

            return [
                'status' => 'success',
                'message' => "L'élève {$student->name} a été inscrit avec succès dans la base de données.",
                'student' => [
                    'id'               => $student->id,
                    'nom_complet'      => $student->name,
                    'niveau'           => $student->level,
                    'telephone_parent' => $student->parent_phone,
                ],
            ];
        }

        return [
            'status' => 'error',
            'message' => "Fonction inconnue : {$functionName}",
        ];
    }

    /**
     * Réponse de secours intelligente locale lorsque l'API Google Gemini n'est pas joignable ou sans clé configurée.
     */
    private function generateFallbackResponse(User $user, string $message, array $context): string
    {
        $lower = mb_strtolower(trim($message));
        $subject = $context['subject'] ?? ($user->teacher?->subject ?? 'Mathématiques');
        $level = $context['level'] ?? 'Lycée / Baccalauréat';
        $userRole = $user->role ?? 'secretary';

        // Détection de derja tunisienne
        $isDerja = preg_match('/(3asslema|ahla|kifech|chkoun|chnowa|n7eb|billehi|fama|famma|behi|mte3|el\s+|w\s+ken|ya3tik|9ol)/iu', $message);

        // 1. Garde-fou hors sujet
        $offTopicKeywords = ['politique', 'président', 'guerre', 'bitcoin', 'crypto', 'football match', 'film', 'recette gateau', 'jeu', 'playstation', 'horoscope'];
        foreach ($offTopicKeywords as $badWord) {
            if (str_contains($lower, $badWord)) {
                if ($isDerja) {
                    return "Ena l'assistant mte3 Study Center khw. Njewbek ken f 7ajet teb3a el 9raya, les cours, l'emploi du temps, el 7oudhour, les élèves w gestion mte3 el centre.";
                }
                return "Je suis exclusivement dédié à l'assistance du Centre d'Études (Study Center). Je ne peux répondre qu'aux questions relatives à vos cours, plannings, présences, finances du centre ou à la pédagogie.";
            }
        }

        // 2. Directeur : Chiffre d'affaires, clôture, rentabilité
        if ($userRole === 'director' && (str_contains($lower, 'chiffre') || str_contains($lower, 'rentabilité') || str_contains($lower, 'clôture') || str_contains($lower, 'bilan') || str_contains($lower, 'recette'))) {
            if ($isDerja) {
                return "📊 **Suivi Financier & Bilan (Directeur) :**\n\n" .
                    "1. Emchi l'onglet **« Rapports & Bilan »** fel menu.\n" .
                    "2. Tchouf el Chiffre d'affaires brut, les remises absorbées wel Marge nette mte3 el centre.\n" .
                    "3. Les régularisations mte3 les profs tet7seb automatique selon el formule (A, B walla C).\n" .
                    "4. F ekher kol ch'har, tcliqui **« Clôturer le mois »** pour verrouiller les écritures.";
            }
            return "📊 **Pilotage Financier & Bilan (Directeur) :**\n\n" .
                "• **Vue globale** : Rendez-vous dans l'onglet **« Rapports & Bilan »** pour visualiser les recettes, dépenses fixes/variables et la marge nette.\n" .
                "• **Rémunérations** : Consultez les fiches de paie détaillées par enseignant calculées automatiquement (Formules A, B, C).\n" .
                "• **Clôture mensuelle** : Utilisez le bouton **« Clôturer le mois »** à la fin de chaque période pour archiver le bilan certifié.";
        }

        // 3. Secrétaire : Relances impayés parents & Garderie
        if (($userRole === 'secretary' || $userRole === 'director') && (str_contains($lower, 'relance') || str_contains($lower, 'impayé') || str_contains($lower, 'retard paiement') || str_contains($lower, 'khlas'))) {
            if ($isDerja) {
                return "📞 **Modèle de Message de Relance WhatsApp (Parents) :**\n\n" .
                    "« 3asslema madame/monsieur,\nNfakkroukom b li el mensualité mte3 [Nom de l'élève] pour le mois en cours mazzelet en attente fel administration mte3 Study Center.\nFadhlan tnajmou tcharfoune fel centre walla tkallmouna pour régulariser el wadhiya.\nMerci l tfehomkom ! »";
            }
            return "📞 **Modèle de Relance Bienveillante aux Parents :**\n\n" .
                "« Bonjour Chers Parents,\n\n" .
                "Nous espérons que vous allez bien. Sauf erreur de notre part, la mensualité de [Prénom de l'élève] pour le mois en cours est actuellement en attente de règlement au secrétariat du Centre d'Études.\n\n" .
                "Nous vous remercions de bien vouloir régulariser cette situation lors de votre prochain passage ou de nous contacter si besoin.\n\n" .
                "Bien cordialement,\n" .
                "L'Administration — Centre d'Études »";
        }

        // 4. Appel et Présences
        if (str_contains($lower, 'appel') || str_contains($lower, 'présence') || str_contains($lower, '7oudhour') || str_contains($lower, 'ghiyeb')) {
            if ($isDerja) {
                return "📌 **Kifech ta3mel l'appel fel application ?**\n\n" .
                    "1. Emchi l'onglet **« Faire l'Appel »** fel barre à gauche.\n" .
                    "2. Ekhtar el **Groupe** wel **Matière** mte3ek.\n" .
                    "3. El date tkoun mrigla b date lyoum par défaut.\n" .
                    "4. Tnajjem tcliqui **« Tous Présents »** f clique wa7da, walla tbadel bel we7ed bel we7ed (Présent, Absent, Retard, Excusé).\n" .
                    "5. Clic **« Enregistrer et valider l'appel »** pour sauvegarder.";
            }
            return "📌 **Comment faire l'appel sur Study Center ?**\n\n" .
                "1. Rendez-vous dans l'onglet **« Faire l'Appel »** dans le menu latéral.\n" .
                "2. Sélectionnez le **Groupe** d'élèves et la **Matière**.\n" .
                "3. La date du jour est pré-sélectionnée par défaut.\n" .
                "4. Utilisez le bouton rapide **« Tous Présents »** ou pointez individuellement chaque élève (*Présent*, *Absent*, *Retard*, *Excusé*).\n" .
                "5. Vous pouvez ajouter une observation (motif, retard...).\n" .
                "6. Cliquez sur **« Enregistrer et valider l'appel »** pour consigner la séance.";
        }

        // 5. Emploi du temps / Planning
        if (str_contains($lower, 'emploi') || str_contains($lower, 'planning') || str_contains($lower, 'salle') || str_contains($lower, 'wa9t')) {
            if ($isDerja) {
                return "📅 **Emploi du temps :**\n\n" .
                    "• Tnajjem tchouf les séances el kol fel onglet **« Emploi du temps »**.\n" .
                    "• Kol séance fiha l'horaire, la salle attribuée, wel groupe.\n" .
                    "• Détection automatique mte3 les conflits de salle b rouge ken fama overlap.\n" .
                    "• Tnajjem zeda t'imprimi l'emploi b bouton **« Imprimer le planning »**.";
            }
            return "📅 **Gestion de l'Emploi du Temps :**\n\n" .
                "• Consultez tous les créneaux hebdomadaires dans l'onglet **« Emploi du temps »**.\n" .
                "• Chaque cours indique la salle attribuée, la matière, le niveau et le groupe.\n" .
                "• L'application détecte et bloque automatiquement les conflits de salle en cas de double réservation.\n" .
                "• Vous pouvez imprimer une version claire via le bouton **« Imprimer le planning »**.";
        }

        // 6. Plan de séance / Cours
        if (str_contains($lower, 'plan') || str_contains($lower, 'séance') || str_contains($lower, 'cours') || str_contains($lower, 'dars')) {
            return "📚 **Modèle de Plan de Séance (2 Heures) — {$subject} ({$level})**\n\n" .
                "⏱️ **00:00 - 00:15 | Accueil & Réactivation (15 min)**\n" .
                "• Prise des présences sur le portail.\n" .
                "• Rappel des 2 notions clés de la séance précédente par questionnement rapide.\n\n" .
                "⏱️ **00:15 - 00:55 | Développement du concept (40 min)**\n" .
                "• Présentation du théorème/règle avec illustration au tableau.\n" .
                "• Exemple guidé pas-à-pas et pièges méthodologiques fréquents.\n\n" .
                "⏱️ **00:55 - 01:45 | Entraînement & Travail dirigé (50 min)**\n" .
                "• Exercice 1 : Application directe (15 min).\n" .
                "• Exercice 2 : Problème d'approfondissement (25 min).\n" .
                "• Passage d'élèves au tableau et remédiation personnalisée.\n\n" .
                "⏱️ **01:45 - 02:00 | Synthèse & Prescription (15 min)**\n" .
                "• Bilan des acquis clés et exercices à préparer pour la prochaine séance.";
        }

        // 7. Exercices et Devoirs
        if (str_contains($lower, 'exercice') || str_contains($lower, 'qcm') || str_contains($lower, 'devoir') || str_contains($lower, 'tamrin')) {
            return "📝 **Série d'Exercices Recommandée — {$subject} ({$level})**\n\n" .
                "**Exercice 1 (Socle / 5 pts) :**\n" .
                "Application directe de la définition ou formule principale. L'élève doit justifier chaque étape en 2 lignes claires.\n\n" .
                "**Exercice 2 (Approfondissement / 7 pts) :**\n" .
                "Résolution d'un problème combinant deux notions du programme. Attention à la rigueur de rédaction et aux unités.\n\n" .
                "**Exercice 3 (Type Examen / 8 pts) :**\n" .
                "Problème de synthèse avec question ouverte. Sollicite l'esprit d'initiative et la démarche d'investigation.";
        }

        // 8. Finances et Rémunération
        if (str_contains($lower, 'rémunération') || str_contains($lower, 'salaire') || str_contains($lower, 'flous') || str_contains($lower, 'remise') || str_contains($lower, 'paiement')) {
            if ($isDerja) {
                return "💰 **Régularisation & Rémunération :**\n\n" .
                    "• Les formules de rémunération fel centre : Pourcentage (Formule C), Montant fixe/élève (Formule B), ou Part fixe centre (Formule A).\n" .
                    "• Les remises mte3 el tledha l'administration tkammelhom men 3andha, l'enseignant yekhou 7a99ou kemel.\n" .
                    "• Dès ma tetsallem el flous, el statut yetbaddel **« Rémunéré »** direct.";
            }
            return "💰 **Rémunération & Finances Enseignants :**\n\n" .
                "• Le centre gère trois formules contractuelles : Pourcentage (Formule C), Tarif fixe par élève (Formule B), ou Part fixe administration (Formule A).\n" .
                "• **Règle clé** : Les remises accordées à certains élèves sont entièrement absorbées par le centre d'études.\n" .
                "• Le statut de paiement passe à **« Payé / Rémunéré »** dès la remise des fonds par la direction.";
        }

        // 7. Message Parents
        if (str_contains($lower, 'parent') || str_contains($lower, 'message') || str_contains($lower, 'sms') || str_contains($lower, 'whatsapp')) {
            return "✉️ **Modèle de Message Professionnel aux Parents :**\n\n" .
                "« Chers parents,\n\n" .
                "Je vous contacte au sujet de [Prénom de l'élève] pour son suivi en {$subject} au Centre d'Études.\n" .
                "Son investissement en classe est très positif. Afin de consolider les acquis, nous vous invitons à l'encourager à refaire les exercices d'application corrigés avant notre prochaine séance.\n\n" .
                "Restant à votre entière écoute pour toute question.\n" .
                "Bien cordialement,\n" .
                "L'Enseignant de {$subject} — Centre d'Études »";
        }

        // 8. Règle « élève » / Inscription
        if (str_contains($lower, 'élève') || str_contains($lower, 'eleve') || str_contains($lower, 'inscri') || str_contains($lower, 'tilmidh')) {
            if ($isDerja) {
                return "👨‍🎓 **Kifech tzid / tqayed tlemidha (Élève) fel application ?**\n\n" .
                    "1. Emchi l'onglet **« Élèves »** fel menu à gauche.\n" .
                    "2. Encliqui 3la el bouton **« Ajouter un élève »** (el fou9 3al imin) bech te7el el modale **« Inscrire un nouvel élève »**.\n" .
                    "3. 3ammer el informations mte3ou :\n" .
                    "   • **Nom et prénom** de l'élève.\n" .
                    "   • **Niveau scolaire** (Programme tunisien : Primaire, Collège, Lycée walla Bac).\n" .
                    "   • **Matières** suivies wel prof mte3 kol matière.\n" .
                    "   • **Coordonnées des parents** (Numéro de téléphone pour le suivi et les reçus).\n" .
                    "4. Affecter l'élève à un groupe correspondant.\n" .
                    "5. Clic sur **« Enregistrer »** pour valider l'inscription.";
            }

            return "👨‍🎓 **Comment ajouter / inscrire un élève sur Study Center ?**\n\n" .
                "Pour inscrire un nouvel élève, suivez ces étapes simples :\n\n" .
                "1. Rendez-vous dans l'onglet **« Élèves »** dans le menu latéral gauche.\n" .
                "2. Cliquez sur le bouton **« Ajouter un élève »** en haut à droite pour ouvrir la modale **« Inscrire un nouvel élève »**.\n" .
                "3. Renseignez les informations demandées :\n" .
                "   • **Nom** : Nom complet de l'élève.\n" .
                "   • **Niveau** : Niveau scolaire officiel tunisien (Primaire, Collège, Lycée ou Baccalauréat avec la section).\n" .
                "   • **Matières** : Matières suivies et enseignants assignés (ou garderie parascolaire).\n" .
                "   • **Coordonnées des parents** : Numéro de téléphone des parents/tuteur (essentiel pour le suivi, les reçus et les relances).\n" .
                "4. Affectez l'élève à son groupe d'étude si nécessaire.\n" .
                "5. Cliquez sur le bouton **« Enregistrer »** pour finaliser l'inscription.";
        }

        // Réponse générique d'accueil
        if ($isDerja) {
            return "3asslema cher(e) {$user->name} ! Ena l'assistant IA mte3 Study Center.\n\n" .
                "Tnajjem tes'elni 3la :\n" .
                "• Kifech ta3mel l'appel w tthabbet fel 7oudhour\n" .
                "• Planning w séances mte3ek\n" .
                "• Préparation mte3 des plans de cours (2h) w exercices\n" .
                "• Rémunération w les groupes mte3ek\n\n" .
                "Chnowa n3awnek lyoum ?";
        }

        return "Bonjour {$user->name} ! Je suis votre tuteur et assistant IA dédié à l'application Study Center.\n\n" .
            "Je peux vous accompagner au quotidien pour :\n" .
            "• **Gérer vos cours** : Préparer un déroulé de séance, générer des exercices progressifs avec corrigés.\n" .
            "• **Gérer votre classe** : Faire l'appel, suivre l'assiduité, rédiger un mot aux parents.\n" .
            "• **Comprendre l'application** : Emploi du temps, répartition des salles, formules de rémunération.\n\n" .
            "Que souhaitez-vous explorer ou préparer ensemble ?";
    }
}
