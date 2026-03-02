<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Bulletin;
use App\Entity\ReportCardLine;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * Tests de l'entité Bulletin : logique métier (moyenne, mention, statut publié/révoqué).
 * Pas de test des simples getters/setters Doctrine.
 */
final class BulletinTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setName('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean@test.com');
        $user->setRole('etudiant');
        $user->setPassword('hash');
        $user->setNumtel('12345678');
        return $user;
    }

    public function testComputeAverageWithLines(): void
    {
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');

        $l1 = new ReportCardLine();
        $l1->setBulletin($bulletin);
        $l1->setModuleName('Maths');
        $l1->setNote(10.0);
        $l1->setCoefficient(2.0);
        $bulletin->addReportCardLine($l1);

        $l2 = new ReportCardLine();
        $l2->setBulletin($bulletin);
        $l2->setModuleName('Français');
        $l2->setNote(14.0);
        $l2->setCoefficient(2.0);
        $bulletin->addReportCardLine($l2);

        self::assertSame(12.0, $bulletin->computeAverage());
    }

    public function testComputeAverageEmptyLinesReturnsZero(): void
    {
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');

        self::assertSame(0.0, $bulletin->computeAverage());
    }

    public function testComputeAverageWeighted(): void
    {
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');

        $l1 = new ReportCardLine();
        $l1->setBulletin($bulletin);
        $l1->setModuleName('A');
        $l1->setNote(10.0);
        $l1->setCoefficient(1.0);
        $bulletin->addReportCardLine($l1);

        $l2 = new ReportCardLine();
        $l2->setBulletin($bulletin);
        $l2->setModuleName('B');
        $l2->setNote(14.0);
        $l2->setCoefficient(3.0);
        $bulletin->addReportCardLine($l2);

        self::assertSame(13.0, $bulletin->computeAverage());
    }

    public function testComputeMention(): void
    {
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');

        $bulletin->setAverage(16.5);
        self::assertSame('Très Bien', $bulletin->computeMention());

        $bulletin->setAverage(14.0);
        self::assertSame('Bien', $bulletin->computeMention());

        $bulletin->setAverage(12.0);
        self::assertSame('Assez Bien', $bulletin->computeMention());

        $bulletin->setAverage(10.0);
        self::assertSame('Passable', $bulletin->computeMention());

        $bulletin->setAverage(8.0);
        self::assertSame('Insuffisant', $bulletin->computeMention());
    }

    public function testComputeMentionReturnsNullWhenAverageNull(): void
    {
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');
        $ref = new \ReflectionClass($bulletin);
        $prop = $ref->getProperty('average');
        $prop->setAccessible(true);
        $prop->setValue($bulletin, null);

        self::assertNull($bulletin->computeMention());
    }

    public function testIsPublished(): void
    {
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');

        $bulletin->setStatus('Brouillon');
        self::assertFalse($bulletin->isPublished());

        $bulletin->setStatus('Publié');
        self::assertTrue($bulletin->isPublished());
    }

    public function testIsRevoked(): void
    {
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');

        self::assertFalse($bulletin->isRevoked());
        $bulletin->setRevokedAt(new \DateTimeImmutable());
        self::assertTrue($bulletin->isRevoked());
    }

    public function testValidateAcademicYearValidFormat(): void
    {
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');
        $bulletin->setStatus('Brouillon');
        $bulletin->setMention('Passable');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($bulletin);
        $academicYearViolations = [];
        foreach ($violations as $v) {
            if ($v->getPropertyPath() === 'academicYear') {
                $academicYearViolations[] = $v;
            }
        }
        self::assertCount(0, $academicYearViolations);
    }

    public function testValidateAcademicYearInvalidFormat(): void
    {
        $bulletin = new Bulletin();
        $bulletin->setStudent($this->createUser());
        $bulletin->setAcademicYear('2025-2026');
        $bulletin->setSemester('Semestre 1');
        $bulletin->setStatus('Brouillon');
        $bulletin->setMention('Passable');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($bulletin);
        $paths = array_map(fn ($v) => $v->getPropertyPath(), iterator_to_array($violations));
        self::assertContains('academicYear', $paths);
    }
}
