<?php

namespace App\Service;

use App\Entity\Exam;
use App\Entity\ExamSubmission;
use Symfony\Component\Process\Process;

/**
 * Extracts text from submission PDFs and computes pairwise similarity to detect suspicious copies.
 */
final class ExamSimilarityService
{
    private const SIMILARITY_SUSPICIOUS_THRESHOLD = 60.0;
    private const SIMILARITY_HIGH_THRESHOLD = 80.0;
    private const MIN_TEXT_LENGTH = 50;

    public function __construct(
        private readonly string $projectDir,
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

                $percent = $this->similarityPercent($textA, $textB);
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
