<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Course;
use App\Entity\Exam;
use PHPUnit\Framework\TestCase;

/**
 * Tests de l'entité Exam : getEffectiveCoefficient et getEffectiveModuleName (délégation au cours si lié).
 */
final class ExamTest extends TestCase
{
    public function testGetEffectiveCoefficientUsesCourseWhenPresent(): void
    {
        $course = new Course();
        $course->setTitle('Maths');
        $course->setCoefficient(3.0);

        $exam = new Exam();
        $exam->setTitle('DS Maths');
        $exam->setType('ds');
        $exam->setCoefficient(1.0);
        $exam->setCourse($course);

        self::assertSame(3.0, $exam->getEffectiveCoefficient());
    }

    public function testGetEffectiveCoefficientUsesExamWhenNoCourse(): void
    {
        $exam = new Exam();
        $exam->setTitle('DS Maths');
        $exam->setType('ds');
        $exam->setCoefficient(2.0);
        $exam->setCourse(null);

        self::assertSame(2.0, $exam->getEffectiveCoefficient());
    }

    public function testGetEffectiveCoefficientUsesExamWhenCourseHasNoCoefficient(): void
    {
        $course = new Course();
        $course->setTitle('Maths');
        $course->setCoefficient(null);

        $exam = new Exam();
        $exam->setTitle('DS Maths');
        $exam->setType('ds');
        $exam->setCoefficient(2.0);
        $exam->setCourse($course);

        self::assertSame(2.0, $exam->getEffectiveCoefficient());
    }

    public function testGetEffectiveModuleNameUsesCourseTitleWhenPresent(): void
    {
        $course = new Course();
        $course->setTitle('Mathématiques');

        $exam = new Exam();
        $exam->setTitle('DS 1');
        $exam->setType('ds');
        $exam->setModuleName('Maths');
        $exam->setCourse($course);

        self::assertSame('Mathématiques', $exam->getEffectiveModuleName());
    }

    public function testGetEffectiveModuleNameUsesExamModuleNameWhenNoCourse(): void
    {
        $exam = new Exam();
        $exam->setTitle('DS 1');
        $exam->setType('ds');
        $exam->setModuleName('Informatique');
        $exam->setCourse(null);

        self::assertSame('Informatique', $exam->getEffectiveModuleName());
    }

    public function testGetEffectiveModuleNameReturnsNullWhenNoCourseAndNoModuleName(): void
    {
        $exam = new Exam();
        $exam->setTitle('DS 1');
        $exam->setType('ds');
        $exam->setModuleName(null);
        $exam->setCourse(null);

        self::assertNull($exam->getEffectiveModuleName());
    }
}
