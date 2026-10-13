<?php

namespace App\Services;

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
        $model = config('services.gemini.model', 'gemini-1.5-flash');
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

        $url = "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";

        try {
            $payload = [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048,
                ],
            ];

            $response = Http::timeout(25)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $replyText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (!empty($replyText)) {
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
            Log::warning("Gemini API Error ({$response->status()}): " . $errorMessage);

            // Secours gracieux pour ne pas bloquer l'utilisateur
            return [
                'status' => 'success',
                'reply' => $this->generateFallbackResponse($user, $message, $context),
                'model' => 'fallback-assisted',
                'is_fallback' => true,
                'api_warning' => "API Gemini temporairement indisponible ({$errorMessage}). Réponse fournie par le moteur pédagogique local.",
            ];

        } catch (\Throwable $e) {
            Log::error('Erreur connexion Gemini: ' . $e->getMessage());

            return [
                'status' => 'success',
                'reply' => $this->generateFallbackResponse($user, $message, $context),
                'model' => 'fallback-assisted',
                'is_fallback' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Construit le prompt système (System Instruction) avec rôles, fonctionnalités de l'app et garde-fous stricts.
     */
    private function buildSystemInstruction(User $user, array $context): string
    {
        $roleLabel = match ($user->role) {
            'director' => 'Directeur / Direction Générale',
            'teacher' => 'Enseignant / Professeur',
            default => 'Personnel Administratif / Secrétaire',
        };

        $teacherInfo = '';
        if ($user->role === 'teacher' && $user->teacher) {
            $t = $user->teacher;
            $teacherInfo = "\n- Matière(s) de l'enseignant : {$t->subject}\n- Séances hebdo : {$t->weekly_lessons}\n- Téléphone : {$t->phone}";
        }

        $extraContext = '';
        if (!empty($context['subject'])) {
            $extraContext .= "\n- Matière en cours d'étude : {$context['subject']}";
        }
        if (!empty($context['level'])) {
            $extraContext .= "\n- Niveau scolaire sélectionné : {$context['level']}";
        }

        return <<<PROMPT
Tu es le tuteur et assistant IA officiel de l'application « Study Center App » (Centre d'Études pour le soutien scolaire et parascolaire en Tunisie).

### IDENTITÉ DE L'UTILISATEUR CONNECTÉ :
- Nom : {$user->name}
- Rôle : {$roleLabel} ({$user->role})
- Email : {$user->email}{$teacherInfo}{$extraContext}

### RÈGLE LINGUISTIQUE ESSENTIELLE :
- Détecte automatiquement la langue ou le dialecte utilisé par l'utilisateur.
- Si l'utilisateur s'adresse à toi en FRANÇAIS, réponds en français soigné, professionnel et pédagogique.
- Si l'utilisateur s'adresse à toi en DÉRJA TUNISIENNE (Arabe dialectal tunisien en caractères latins 'arabizi' ou arabes), RÉPONDS OBLIGATOIREMENT EN DÉRJA TUNISIENNE authentique, chaleureuse et naturelle (exemples: "3asslema", "Ahla bik", "Kifech n3awnek", "Tnajjem temchi l onglet...", "Famma bouton...").
- Tu peux mixer le français et la derja comme c'est l'usage courant en Tunisie chez les enseignants et éducateurs.

### GARDE-FOUS STRICTS (GUARDRAILS) - NON NÉGOCIABLE :
- Tu dois STRICTEMENT REFUSER tout sujet qui sort du cadre de l'application Study Center, de l'éducation, des cours, des devoirs, des plannings, des présences, des finances de cours ou de la pédagogie scolaire tunisienne.
- Sujets INTERDITS : politique générale, polémiques, jeux vidéo, célébrités, crypto-monnaies, piratage, recettes de cuisine sans rapport avec l'école, conseils médicaux hors premiers soins scolaires, astrologie, etc.
- Réponse en cas de question hors sujet :
  * En français : « Je suis exclusivement dédié à l'assistance du Centre d'Études (Study Center). Je ne peux répondre qu'aux questions relatives à vos cours, vos plannings, vos élèves, vos présences, aux finances du centre ou à la pédagogie scolaire. »
  * En derja tunisienne : « Ena l'assistant mte3 Study Center khw. Njewbek ken f 7ajet teb3a el 9raya, les cours, l'emploi du temps, el 7oudhour, les élèves w gestion mte3 el centre. »

### CONNAISSANCES APPROFONDIES DE L'APPLICATION « STUDY CENTER APP » :
1. **Emploi du temps & Salles** : Planning hebdomadaire par jour (Lundi à Dimanche), gestion des salles (ex: Salle 1, Salle 2) avec détection bloquante des conflits de salle, et avertissement pour conflit de groupe.
2. **Présences & Appel** : Pointage par séance en 1 clic (Présent [vert], Absent [rouge], En retard [orange], Excusé [bleu]), calcul automatique du taux d'assiduité et comptabilisation des séances réelles pour la facturation des élèves.
3. **Pédagogie & Enseignants** :
   - Structure de séances types de 2 heures (accueil/réactivation 15 min, notion clé 40 min, pratique dirigée 50 min, bilan 15 min).
   - Séries d'exercices progressifs (Application directe, Approfondissement, Synthèse type Examen/Bac) conformes au programme tunisien (Primaire, Collège 7ème-9ème, Lycée 1ère-4ème Bac).
   - Modèles de messages bienveillants et clairs pour les parents (WhatsApp/SMS) pour signaler absences ou féliciter les progrès.
4. **Finances & Rémunération Enseignants** :
   - Formule A (fixed_center) : Part fixe centre par élève (ex: 20 TND/élève) — le prof prend le reste.
   - Formule B (fixed) : Tarif fixe par élève inscrit pour l'enseignant (ex: 15 TND/élève).
   - Formule C (percentage) : Pourcentage sur le montant des cours (ex: 70% prof / 30% centre).
   - Les remises accordées aux élèves sont intégralement prises en charge par la direction du centre.
   - Statut de paiement : L'administration bascule la rémunération en "Payé" dès remise physique des fonds.
5. **Garderie Parascolaire** : Pointage journalier et forfaits mensuels pour les enfants accueillis.
6. **Comptabilité & Bilan** : Réservé au directeur (recettes, dépenses fixes et variables, clôture mensuelle).

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
     * Réponse de secours intelligente locale lorsque l'API Google Gemini n'est pas joignable ou sans clé configurée.
     */
    private function generateFallbackResponse(User $user, string $message, array $context): string
    {
        $lower = mb_strtolower(trim($message));
        $subject = $context['subject'] ?? ($user->teacher?->subject ?? 'Mathématiques');
        $level = $context['level'] ?? 'Lycée / Baccalauréat';

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

        // 2. Appel et Présences
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

        // 3. Emploi du temps / Planning
        if (str_contains($lower, 'emploi') || str_contains($lower, 'planning') || str_contains($lower, 'salle') || str_contains($lower, 'wa9t')) {
            if ($isDerja) {
                return "📅 **Emploi du temps mte3ek :**\n\n" .
                    "• Tnajjem tchouf les séances mte3ek el kol fel onglet **« Emploi du temps »**.\n" .
                    "• Les séances mte3 lyoum yabdew tal3in mel fou9 f cadre mlawen b bouton direct pour faire l'appel.\n" .
                    "• Kol séance fiha l'horaire, la salle attribuee, wel groupe.\n" .
                    "• Tnajjem zeda t'imprimi l'emploi b bouton **« Imprimer mon planning »**.";
            }
            return "📅 **Votre Emploi du Temps :**\n\n" .
                "• Consultez tous vos créneaux hebdomadaires dans l'onglet **« Emploi du temps »**.\n" .
                "• Vos cours du jour apparaissent en tête de page avec accès direct à l'appel.\n" .
                "• Chaque fiche indique la salle de classe attribuée, le niveau et le groupe.\n" .
                "• Vous pouvez imprimer une version propre via le bouton **« Imprimer mon planning »**.";
        }

        // 4. Plan de séance / Cours
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

        // 5. Exercices et Devoirs
        if (str_contains($lower, 'exercice') || str_contains($lower, 'qcm') || str_contains($lower, 'devoir') || str_contains($lower, 'tamrin')) {
            return "📝 **Série d'Exercices Recommandée — {$subject} ({$level})**\n\n" .
                "**Exercice 1 (Socle / 5 pts) :**\n" .
                "Application directe de la définition ou formule principale. L'élève doit justifier chaque étape en 2 lignes claires.\n\n" .
                "**Exercice 2 (Approfondissement / 7 pts) :**\n" .
                "Résolution d'un problème combinant deux notions du programme. Attention à la rigueur de rédaction et aux unités.\n\n" .
                "**Exercice 3 (Type Examen / 8 pts) :**\n" .
                "Problème de synthèse avec question ouverte. Sollicite l'esprit d'initiative et la démarche d'investigation.";
        }

        // 6. Finances et Rémunération
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
