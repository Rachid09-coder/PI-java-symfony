<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Bulletin;
use App\Entity\ReportCardLine;
use App\Entity\User;
use App\Repository\ExamRepository;
use App\Repository\ExamSubmissionRepository;
use App\Service\GradeCalculationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests du service de calcul des notes et bulletins.
 * Logique métier : mention selon moyenne, recalcul moyenne bulletin, formules CC/DS/Exam.
 */
final class GradeCalculationServiceTest extends TestCase
{
    private function createGradeCalculationService(): GradeCalculationService
    {
        return new GradeCalculationService(
            $this->createMock(ExamRepository::class),
            $this->createMock(ExamSubmissionRepository::class),
            $this->createMock(EntityManagerInterface::class)
        );
    }

    public function testComputeMentionTresBien(): void
    {
        $service = $this->createGradeCalculationService();
        self::assertSame('Très Bien', $service->computeMention(16.0));
        self::assertSame('Très Bien', $service->computeMention(18.5));
        self::assertSame('Très Bien', $service->computeMention(20.0));
    }

    public function testComputeMentionBien(): void
    {
        $service = $this->createGradeCalculationService();
        self::assertSame('Bien', $service->computeMention(14.0));
        self::assertSame('Bien', $service->computeMention(15.99));
    }

    public function testComputeMentionAssezBien(): void
    {
        $service = $this->createGradeCalculationService();
        self::assertSame('Assez Bien', $service->computeMention(12.0));
        self::assertSame('Assez Bien', $service->computeMention(13.99));
    }

    public function testComputeMentionPassable(): void
    {
        $service = $this->createGradeCalculationService();
        self::assertSame('Passable', $service->computeMention(10.0));
        self::assertSame('Passable', $service->computeMention(11.99));
    }

    public function testComputeMentionInsuffisant(): void
    {
        $service = $this->createGradeCalculationService();
        self::assertSame('Insuffisant', $service->computeMention(0.0));
        self::assertSame('Insuffisant', $service->computeMention(5.5));
        self::assertSame('Insuffisant', $service->computeMention(9.99));
    }

    public function testRecalculateBulletinAverageWithLines(): void
    {
        $service = $this->createGradeCalculationService();
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');
        $bulletin->setStatus('Brouillon');
        $bulletin->setMention('Passable');

        $line1 = new ReportCardLine();
        $line1->setBulletin($bulletin);
        $line1->setModuleName('Maths');
        $line1->setNote(10.0);
        $line1->setCoefficient(2.0);
        $bulletin->addReportCardLine($line1);

        $line2 = new ReportCardLine();
        $line2->setBulletin($bulletin);
        $line2->setModuleName('Français');
        $line2->setNote(14.0);
        $line2->setCoefficient(2.0);
        $bulletin->addReportCardLine($line2);

        $service->recalculateBulletinAverage($bulletin);

        self::assertSame(12.0, $bulletin->getAverage());
        self::assertSame('Assez Bien', $bulletin->getMention());
    }

    public function testRecalculateBulletinAverageEmptyLinesSetsZero(): void
    {
        $service = $this->createGradeCalculationService();
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');
        $bulletin->setStatus('Brouillon');
        $bulletin->setMention('Passable');
        $bulletin->setAverage(15.0);

        $service->recalculateBulletinAverage($bulletin);

        self::assertSame(0.0, $bulletin->getAverage());
        // Quand il n'y a pas de lignes, le service ne met pas à jour la mention (return early)
        self::assertSame('Passable', $bulletin->getMention());
    }

    public function testRecalculateBulletinAverageWeighted(): void
    {
        $service = $this->createGradeCalculationService();
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');
        $bulletin->setStatus('Brouillon');
        $bulletin->setMention('');

        $line1 = new ReportCardLine();
        $line1->setBulletin($bulletin);
        $line1->setModuleName('A');
        $line1->setNote(10.0);
        $line1->setCoefficient(1.0);
        $bulletin->addReportCardLine($line1);

        $line2 = new ReportCardLine();
        $line2->setBulletin($bulletin);
        $line2->setModuleName('B');
        $line2->setNote(14.0);
        $line2->setCoefficient(3.0);
        $bulletin->addReportCardLine($line2);

        $service->recalculateBulletinAverage($bulletin);

        self::assertSame(13.0, $bulletin->getAverage()); // (10*1 + 14*3) / 4 = 52/4
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setName('Test');
        $user->setPrenom('User');
        $user->setEmail('test@test.com');
        $user->setRole('etudiant');
        $user->setPassword('hash');
        $user->setNumtel('12345678');
        return $user;
    }
}
