<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Bulletin;
use App\Entity\ReportCardLine;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests de l'entité ReportCardLine : calcul de la note pondérée CC×10% + DS×20% + Exam×70%.
 */
final class ReportCardLineTest extends TestCase
{
    private function createBulletin(): Bulletin
    {
        $user = new User();
        $user->setName('Test');
        $user->setPrenom('User');
        $user->setEmail('test@test.com');
        $user->setRole('etudiant');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $bulletin = new Bulletin();
        $bulletin->setStudent($user);
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');
        return $bulletin;
    }

    public function testCalculateNoteViaSetters(): void
    {
        $bulletin = $this->createBulletin();
        $line = new ReportCardLine();
        $line->setBulletin($bulletin);
        $line->setModuleName('Maths');
        $line->setCoefficient(1.0);

        $line->setNoteCC(10.0);
        $line->setNoteDS(12.0);
        $line->setNoteExam(14.0);

        self::assertSame(13.2, $line->getNote()); // 10*0.1 + 12*0.2 + 14*0.7 = 1 + 2.4 + 9.8 = 13.2
    }

    public function testCalculateNoteOnlyExam(): void
    {
        $bulletin = $this->createBulletin();
        $line = new ReportCardLine();
        $line->setBulletin($bulletin);
        $line->setModuleName('Maths');
        $line->setCoefficient(1.0);
        $line->setNoteCC(null);
        $line->setNoteDS(null);
        $line->setNoteExam(15.0);

        self::assertSame(10.5, $line->getNote()); // 0 + 0 + 15*0.7 = 10.5
    }

    public function testCalculateNoteAllZero(): void
    {
        $bulletin = $this->createBulletin();
        $line = new ReportCardLine();
        $line->setBulletin($bulletin);
        $line->setModuleName('Maths');
        $line->setCoefficient(1.0);
        $line->setNoteCC(0.0);
        $line->setNoteDS(0.0);
        $line->setNoteExam(0.0);

        self::assertSame(0.0, $line->getNote());
    }

    public function testCalculateNoteRoundedToTwoDecimals(): void
    {
        $bulletin = $this->createBulletin();
        $line = new ReportCardLine();
        $line->setBulletin($bulletin);
        $line->setModuleName('Maths');
        $line->setCoefficient(1.0);
        $line->setNoteCC(10.0);
        $line->setNoteDS(10.0);
        $line->setNoteExam(10.0);

        self::assertSame(10.0, $line->getNote());
    }
}
