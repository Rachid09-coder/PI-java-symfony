<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Génère des plans de cours et du contenu pédagogique via OpenAI, Groq ou Gemini (selon les clés configurées dans .env).
 */
final class AiCourseGeneratorService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $openaiApiKey = null,
        private readonly ?string $groqApiKey = null,
        private readonly ?string $geminiApiKey = null,
    ) {
    }

    /**
     * Generate a structured course plan using AI (or mock if no API key).
     * Tries in order: OpenAI → Groq → Gemini.
     *
     * @return array{outline: string[], objectives: string[], key_concepts: string[], suggested_exercises: string[], quiz_questions: array<int, array{question: string, options?: string[], correct?: string}>}
     */
    public function generatePlan(string $courseTitle, string $level, string $duration): array
    {
        $prompt = $this->buildPrompt($courseTitle, $level, $duration);

        $response = $this->callOpenAi($prompt)
            ?? $this->callGroq($prompt)
            ?? $this->callGemini($prompt);

        if ($response !== null) {
            return $response;
        }

        return $this->getMockPlan($courseTitle, $level, $duration);
    }

    /**
     * Generate full course content (lesson text) from the plan, for student display.
     * Tries in order: OpenAI → Groq → Gemini.
     */
    public function generateContent(string $courseTitle, string $level, string $duration, array $plan): string
    {
        $outlineStr = implode("\n", $plan['outline'] ?? []);
        $objectivesStr = implode("\n", $plan['objectives'] ?? []);
        $prompt = $this->buildContentPrompt($courseTitle, $level, $duration, $outlineStr, $objectivesStr);

        $content = $this->callOpenAiContent($prompt)
            ?? $this->callGroqContent($prompt)
            ?? $this->callGeminiContent($prompt);

        return $content !== null ? $content : $this->getMockContent($courseTitle, $level, $duration, $plan);
    }

    private function buildContentPrompt(string $title, string $level, string $duration, string $outline, string $objectives): string
    {
        return <<<PROMPT
Tu es un formateur. Rédige le contenu complet du cours en français, au format texte (pas de JSON, pas de markdown).

Contexte:
- Titre: {$title}
- Niveau: {$level}
- Durée: {$duration}

Plan du cours:
{$outline}

Objectifs:
{$objectives}

Rédige un contenu pédagogique détaillé (plusieurs paragraphes par section), clair et structuré, que l'étudiant peut lire directement. Utilise des titres de section sous la forme "## 1. Introduction" etc. et des paragraphes séparés par des retours à la ligne. N'utilise pas de balises HTML. Réponds uniquement avec le texte du cours.
PROMPT;
    }

    private function callOpenAiContent(string $prompt): ?string
    {
        if ($this->openaiApiKey === null || $this->openaiApiKey === '') {
            return null;
        }
        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.6,
                ],
                'timeout' => 90,
            ]);
            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            return trim($content) !== '' ? trim($content) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Groq (API compatible OpenAI). */
    private function callGroq(string $prompt): ?array
    {
        if ($this->groqApiKey === null || $this->groqApiKey === '') {
            return null;
        }
        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                ],
                'timeout' => 60,
            ]);
            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $content = trim($content);
            if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $m)) {
                $content = trim($m[1]);
            }
            $decoded = json_decode($content, true);
            return \is_array($decoded) ? $this->normalizePlan($decoded) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function callGroqContent(string $prompt): ?string
    {
        if ($this->groqApiKey === null || $this->groqApiKey === '') {
            return null;
        }
        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.6,
                ],
                'timeout' => 90,
            ]);
            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            return trim($content) !== '' ? trim($content) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Google Gemini REST API. */
    private function callGemini(string $prompt): ?array
    {
        if ($this->geminiApiKey === null || $this->geminiApiKey === '') {
            return null;
        }
        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($this->geminiApiKey);
            $response = $this->httpClient->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 8192,
                        'responseMimeType' => 'application/json',
                    ],
                ],
                'timeout' => 60,
            ]);
            $data = $response->toArray();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $decoded = json_decode(trim($text), true);
            return \is_array($decoded) ? $this->normalizePlan($decoded) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function callGeminiContent(string $prompt): ?string
    {
        if ($this->geminiApiKey === null || $this->geminiApiKey === '') {
            return null;
        }
        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($this->geminiApiKey);
            $response = $this->httpClient->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature' => 0.6,
                        'maxOutputTokens' => 8192,
                    ],
                ],
                'timeout' => 90,
            ]);
            $data = $response->toArray();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            return trim($content) !== '' ? trim($content) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getMockContent(string $title, string $level, string $duration, array $plan): string
    {
        $outline = $plan['outline'] ?? [];
        $objectives = $plan['objectives'] ?? [];
        $concepts = $plan['key_concepts'] ?? [];
        $s = "## " . $title . "\n\n";
        $s .= "Niveau : " . $level . " | Durée : " . $duration . "\n\n";
        $s .= "### Objectifs pédagogiques\n\n";
        foreach ($objectives as $o) {
            $s .= "• " . $o . "\n";
        }
        $s .= "\n### Contenu du cours\n\n";
        $conceptCount = count($concepts);
        foreach ($outline as $i => $section) {
            $s .= "## " . $section . "\n\n";
            $s .= "Cette section aborde les points essentiels pour maîtriser le sujet. ";
            if ($conceptCount > 0 && isset($concepts[$i % $conceptCount])) {
                $s .= "Concept clé : " . $concepts[$i % $conceptCount] . ". ";
            }
            $s .= "Prenez le temps de lire et de pratiquer les exercices suggérés.\n\n";
        }
        $s .= "### Concepts clés à retenir\n\n";
        foreach ($concepts as $c) {
            $s .= "• " . $c . "\n";
        }
        $s .= "\n---\nContenu généré par l’assistant pédagogique EduSmart.";
        return $s;
    }

    private function buildPrompt(string $title, string $level, string $duration): string
    {
        return <<<PROMPT
Tu es un assistant pédagogique. Génère un plan de cours structuré en français au format JSON strict (pas de markdown, pas de texte autour).

Contexte:
- Titre du cours: {$title}
- Niveau: {$level}
- Durée: {$duration}

Réponds UNIQUEMENT avec un objet JSON valide ayant exactement ces clés (en français):
- "outline": tableau de chaînes (sections/séances du plan, ex: "1. Introduction", "2. Notions de base", ...)
- "objectives": tableau de chaînes (objectifs pédagogiques)
- "key_concepts": tableau de chaînes (concepts clés à maîtriser)
- "suggested_exercises": tableau de chaînes (exercices suggérés avec courte description)
- "quiz_questions": tableau d'objets avec "question" (string), "options" (tableau de 4 réponses), "correct" (index 0-3 de la bonne réponse)

Exemple de structure pour quiz_questions: [{"question": "Qu'est-ce que...?", "options": ["A", "B", "C", "D"], "correct": 0}]

Réponds uniquement avec le JSON, sans commentaire.
PROMPT;
    }

    private function callOpenAi(string $prompt): ?array
    {
        if ($this->openaiApiKey === null || $this->openaiApiKey === '') {
            return null;
        }
        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                ],
                'timeout' => 60,
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';

            $content = trim($content);
            if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $m)) {
                $content = trim($m[1]);
            }
            $decoded = json_decode($content, true);
            if (!\is_array($decoded)) {
                return null;
            }

            return $this->normalizePlan($decoded);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizePlan(array $decoded): array
    {
        return [
            'outline' => \is_array($decoded['outline'] ?? null) ? $decoded['outline'] : [],
            'objectives' => \is_array($decoded['objectives'] ?? null) ? $decoded['objectives'] : [],
            'key_concepts' => \is_array($decoded['key_concepts'] ?? null) ? $decoded['key_concepts'] : [],
            'suggested_exercises' => \is_array($decoded['suggested_exercises'] ?? null) ? $decoded['suggested_exercises'] : [],
            'quiz_questions' => \is_array($decoded['quiz_questions'] ?? null) ? $decoded['quiz_questions'] : [],
        ];
    }

    private function getMockPlan(string $title, string $level, string $duration): array
    {
        return [
            'outline' => [
                '1. Introduction et objectifs du cours',
                '2. Notions fondamentales',
                '3. Mise en pratique guidée',
                '4. Exercices et études de cas',
                '5. Synthèse et évaluation',
            ],
            'objectives' => [
                'Comprendre les concepts de base du sujet.',
                'Savoir appliquer les méthodes vues en cours.',
                'Être capable de réaliser un projet court en autonomie.',
            ],
            'key_concepts' => [
                'Définition et périmètre du domaine',
                'Principes essentiels',
                'Bonnes pratiques et pièges à éviter',
            ],
            'suggested_exercises' => [
                'Exercice 1 : Quiz de vérification des prérequis (5 min).',
                'Exercice 2 : Travail dirigé en binôme sur un cas simple (20 min).',
                'Exercice 3 : Mini-projet à rendre pour la séance suivante.',
            ],
            'quiz_questions' => [
                [
                    'question' => 'Quel est l\'objectif principal de ce cours ?',
                    'options' => ['Maîtriser les bases', 'Approfondir des notions avancées', 'Préparer un examen', 'Découvrir le sujet'],
                    'correct' => 0,
                ],
                [
                    'question' => 'À la fin du cours, l\'étudiant doit être capable de :',
                    'options' => ['Réciter le cours', 'Appliquer les notions en pratique', 'Critiquer la discipline', 'Tout mémoriser'],
                    'correct' => 1,
                ],
                [
                    'question' => 'Le niveau « ' . $level . ' » correspond à :',
                    'options' => ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'],
                    'correct' => 0,
                ],
            ],
        ];
    }
}
