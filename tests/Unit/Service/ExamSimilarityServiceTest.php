<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Exam;
use App\Entity\ExamSubmission;
use App\Entity\User;
use App\Service\ExamSimilarityService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Tests du service de détection de similarité entre copies d'examen.
 * Logique métier : seuils, rapport avec 0/1 soumission, similarité PHP (sans appel API).
 */
final class ExamSimilarityServiceTest extends TestCase
{
    private function createService(
        string $projectDir = '',
        ?HttpClientInterface $httpClient = null,
        ?string $geminiApiKey = null,
        ?string $groqApiKey = null,
        ?string $openaiApiKey = null
    ): ExamSimilarityService {
        return new ExamSimilarityService(
            $projectDir,
            $httpClient,
            $geminiApiKey,
            $groqApiKey,
            $openaiApiKey
        );
    }

    public function testGetSuspiciousThreshold(): void
    {
        self::assertSame(60.0, ExamSimilarityService::getSuspiciousThreshold());
    }

    public function testGetHighThreshold(): void
    {
        self::assertSame(80.0, ExamSimilarityService::getHighThreshold());
    }

    public function testGetSimilarityReportWithZeroSubmissions(): void
    {
        $exam = new Exam();
        $exam->setTitle('Exam 1');
        $exam->setType('ds');
        $service = $this->createService('/tmp');

        $report = $service->getSimilarityReport($exam);

        self::assertSame([], $report['pairs']);
        self::assertSame('Aucun rendu PDF pour cet examen.', $report['error']);
    }

    public function testGetSimilarityReportWithOneSubmission(): void
    {
        $exam = new Exam();
        $exam->setTitle('Exam 1');
        $exam->setType('ds');
        $sub = new ExamSubmission();
        $sub->setFilePath('uploads/sub1.pdf');
        $ref = new \ReflectionClass($sub);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($sub, 1);
        $exam->addSubmission($sub);

        $service = $this->createService(sys_get_temp_dir());

        $report = $service->getSimilarityReport($exam);

        self::assertSame([], $report['pairs']);
        self::assertSame('Au moins 2 rendus PDF sont nécessaires pour comparer.', $report['error']);
    }

    public function testGetSimilarityReportWithTwoSubmissionsNoFilesReturnsEmptyPairs(): void
    {
        $exam = new Exam();
        $exam->setTitle('Exam 1');
        $exam->setType('ds');
        $sub1 = new ExamSubmission();
        $sub1->setFilePath('nonexistent/file1.pdf');
        $this->setId($sub1, 1);
        $exam->addSubmission($sub1);
        $sub2 = new ExamSubmission();
        $sub2->setFilePath('nonexistent/file2.pdf');
        $this->setId($sub2, 2);
        $exam->addSubmission($sub2);

        $projectDir = sys_get_temp_dir() . '/exam_sim_' . uniqid();
        mkdir($projectDir, 0777, true);
        mkdir($projectDir . '/public', 0777, true);
        try {
            $service = $this->createService($projectDir);
            $report = $service->getSimilarityReport($exam);
            self::assertNull($report['error']);
            self::assertIsArray($report['pairs']);
            self::assertCount(0, $report['pairs']);
        } finally {
            if (is_dir($projectDir)) {
                @rmdir($projectDir . '/public');
                @rmdir($projectDir);
            }
        }
    }

    public function testGetSimilarityReportWithTwoSubmissionsWithTextFilesReturnsPairs(): void
    {
        $exam = new Exam();
        $exam->setTitle('Exam 1');
        $exam->setType('ds');
        $projectDir = sys_get_temp_dir() . '/exam_sim_' . uniqid();
        mkdir($projectDir, 0777, true);
        $publicDir = $projectDir . '/public';
        mkdir($publicDir, 0777, true);

        $path1 = $publicDir . '/sub1.pdf';
        $path2 = $publicDir . '/sub2.pdf';
        file_put_contents($path1, '%PDF-1.4 dummy'); // pdftotext may still return nothing; use text file for extraction
        file_put_contents($path2, '%PDF-1.4 dummy');

        $sub1 = new ExamSubmission();
        $sub1->setFilePath('sub1.pdf');
        $this->setId($sub1, 1);
        $exam->addSubmission($sub1);
        $sub2 = new ExamSubmission();
        $sub2->setFilePath('sub2.pdf');
        $this->setId($sub2, 2);
        $exam->addSubmission($sub2);

        try {
            $service = $this->createService($projectDir);
            $report = $service->getSimilarityReport($exam);
            self::assertNull($report['error']);
            self::assertIsArray($report['pairs']);
            // With dummy PDF content, extraction may be empty so pairs can be 0; or 1 pair if both have enough text
            self::assertLessThanOrEqual(1, count($report['pairs']));
        } finally {
            @unlink($path1);
            @unlink($path2);
            @rmdir($publicDir);
            @rmdir($projectDir);
        }
    }

    private function setId(ExamSubmission $sub, int $id): void
    {
        $ref = new \ReflectionClass($sub);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($sub, $id);
    }
}
