<?php

namespace App\Service;

use App\Entity\Exam;
use App\Entity\ExamSubmission;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Extracts text from submission PDFs and computes pairwise similarity to detect suspicious copies.
 * Uses AI (Gemini, then Groq, then OpenAI from .env) when an API key is set; otherwise PHP similar_text().
 */
final class ExamSimilarityService
{
    private const SIMILARITY_SUSPICIOUS_THRESHOLD = 60.0;
    private const SIMILARITY_HIGH_THRESHOLD = 80.0;
    private const MIN_TEXT_LENGTH = 50;
    /** Max chars per text sent to AI to stay within token limits */
    private const MAX_TEXT_FOR_AI = 2500;

    public function __construct(
        private readonly string $projectDir,
        private readonly ?HttpClientInterface $httpClient = null,
        private readonly ?string $geminiApiKey = null,
        private readonly ?string $groqApiKey = null,
        private readonly ?string $openaiApiKey = null,
    ) {
    }

    /**
     * Returns similarity report for an exam: pairs of submissions with similarity percentage.
     *
     * @return array{ pairs: list<array{ a: ExamSubmission, b: ExamSubmission, percent: float, suspicious: bool }>, error: ?string }
     */
    public function getSimilarityReport(Exam $exam): array
    {
        $submissionsWithPdf = [];
        foreach ($exam->getSubmissions() as $s) {
            if ($s->getFilePath() !== null) {
                $submissionsWithPdf[] = $s;
            }
        }

        if (count($submissionsWithPdf) < 2) {
            return [
                'pairs' => [],
                'error' => count($submissionsWithPdf) === 0 ? 'Aucun rendu PDF pour cet examen.' : 'Au moins 2 rendus PDF sont nécessaires pour comparer.',
            ];
        }

        $texts = [];
        foreach ($submissionsWithPdf as $s) {
            $path = $this->projectDir . '/public/' . str_replace('/', \DIRECTORY_SEPARATOR, $s->getFilePath());
            if (!is_file($path)) {
                $texts[$s->getId()] = '';
                continue;
            }
            $texts[$s->getId()] = $this->extractTextFromPdf($path);
        }

        $pairs = [];
        $ids = array_keys($texts);
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $idA = $ids[$i];
                $idB = $ids[$j];
                $textA = $texts[$idA];
                $textB = $texts[$idB];

                if (strlen($textA) < self::MIN_TEXT_LENGTH || strlen($textB) < self::MIN_TEXT_LENGTH) {
                    continue;
                }

                $percent = $this->computeSimilarity($textA, $textB);
                $subA = $this->findSubmissionById($submissionsWithPdf, $idA);
                $subB = $this->findSubmissionById($submissionsWithPdf, $idB);
                if ($subA && $subB) {
                    $pairs[] = [
                        'a' => $subA,
                        'b' => $subB,
                        'percent' => round($percent, 1),
                        'suspicious' => $percent >= self::SIMILARITY_SUSPICIOUS_THRESHOLD,
                    ];
                }
            }
        }

        usort($pairs, fn($x, $y) => $y['percent'] <=> $x['percent']);

        return [
            'pairs' => $pairs,
            'error' => null,
        ];
    }

    private function findSubmissionById(array $submissions, int $id): ?ExamSubmission
    {
        foreach ($submissions as $s) {
            if ($s->getId() === $id) {
                return $s;
            }
        }
        return null;
    }

    private function extractTextFromPdf(string $filePath): string
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
                return $this->normalizeText($text ?? '');
            } catch (\Throwable $e) {
                return '';
            }
        }

        return $this->extractTextViaPdftotext($filePath);
    }

    private function extractTextViaPdftotext(string $filePath): string
    {
        $process = new Process(['pdftotext', '-layout', $filePath, '-']);
        $process->setTimeout(15);
        try {
            $process->run();
            if ($process->isSuccessful()) {
                return $this->normalizeText($process->getOutput());
            }
        } catch (\Throwable $e) {
            // pdftotext not available or failed
        }
        return '';
    }

    private function normalizeText(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    /**
     * Compute similarity 0-100. Uses AI (Gemini → Groq → OpenAI) when a key is set, else PHP similar_text().
     */
    private function computeSimilarity(string $textA, string $textB): float
    {
        $trimA = $this->trimForAi($textA);
        $trimB = $this->trimForAi($textB);

        if ($this->httpClient !== null) {
            $aiPercent = $this->callGeminiSimilarity($trimA, $trimB)
                ?? $this->callGroqSimilarity($trimA, $trimB)
                ?? $this->callOpenAiSimilarity($trimA, $trimB);
            if ($aiPercent !== null) {
                return $aiPercent;
            }
        }

        return $this->similarityPercent($textA, $textB);
    }

    private function trimForAi(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text));
        if (mb_strlen($text) > self::MAX_TEXT_FOR_AI) {
            return mb_substr($text, 0, self::MAX_TEXT_FOR_AI) . '…';
        }
        return $text;
    }

    private function callGeminiSimilarity(string $textA, string $textB): ?float
    {
        if ($this->geminiApiKey === null || $this->geminiApiKey === '') {
            return null;
        }
        try {
            $prompt = <<<PROMPT
Tu es un expert en détection de similarité entre copies d'examen. Compare les deux textes suivants et estime leur similarité (copie, paraphrase, ou indépendants).

Texte 1:
{$textA}

Texte 2:
{$textB}

Réponds UNIQUEMENT par un nombre entre 0 et 100 (0 = totalement différents, 100 = quasi identiques / copie). Aucun autre texte.
PROMPT;
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($this->geminiApiKey);
            $response = $this->httpClient->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 10],
                ],
                'timeout' => 30,
            ]);
            $data = $response->toArray();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $num = (float) preg_replace('/[^0-9.]/', '', trim($content));
            return ($num >= 0 && $num <= 100) ? $num : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function callGroqSimilarity(string $textA, string $textB): ?float
    {
        if ($this->groqApiKey === null || $this->groqApiKey === '') {
            return null;
        }
        try {
            $prompt = "Compare these two exam answers and reply with ONLY one number from 0 to 100 (similarity: 100=identical).\n\nText 1:\n{$textA}\n\nText 2:\n{$textB}";
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.2,
                    'max_tokens' => 10,
                ],
                'timeout' => 30,
            ]);
            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $num = (float) preg_replace('/[^0-9.]/', '', trim($content));
            return ($num >= 0 && $num <= 100) ? $num : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function callOpenAiSimilarity(string $textA, string $textB): ?float
    {
        if ($this->openaiApiKey === null || $this->openaiApiKey === '') {
            return null;
        }
        try {
            $prompt = "Compare these two exam answers and reply with ONLY one number from 0 to 100 (similarity: 100=identical).\n\nText 1:\n{$textA}\n\nText 2:\n{$textB}";
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.2,
                    'max_tokens' => 10,
                ],
                'timeout' => 30,
            ]);
            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $num = (float) preg_replace('/[^0-9.]/', '', trim($content));
            return ($num >= 0 && $num <= 100) ? $num : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function similarityPercent(string $textA, string $textB): float
    {
        $lenA = strlen($textA);
        $lenB = strlen($textB);
        if ($lenA === 0 || $lenB === 0) {
            return 0.0;
        }
        similar_text($textA, $textB, $percent);
        return (float) $percent;
    }

    public static function getSuspiciousThreshold(): float
    {
        return self::SIMILARITY_SUSPICIOUS_THRESHOLD;
    }

    public static function getHighThreshold(): float
    {
        return self::SIMILARITY_HIGH_THRESHOLD;
    }
}
