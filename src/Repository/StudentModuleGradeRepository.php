<?php

namespace App\Repository;

use App\Entity\StudentModuleGrade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentModuleGrade>
 */
class StudentModuleGradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentModuleGrade::class);
    }

    /**
     * Récupère toutes les notes d'un étudiant pour une année/semestre
     */
    public function findByStudentAndPeriod(int $studentId, string $academicYear, string $semester): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.student = :studentId')
            ->andWhere('g.academicYear = :year')
            ->andWhere('g.semester = :semester')
            ->setParameter('studentId', $studentId)
            ->setParameter('year', $academicYear)
            ->setParameter('semester', $semester)
            ->orderBy('g.moduleName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la moyenne générale pondérée d'un étudiant pour une période
     */
    public function calculateAverage(int $studentId, string $academicYear, string $semester): ?float
    {
        $grades = $this->findByStudentAndPeriod($studentId, $academicYear, $semester);
        
        if (empty($grades)) {
            return null;
        }

        $totalWeighted = 0;
        $totalCoefficients = 0;

        foreach ($grades as $grade) {
            $note = $grade->getCalculatedNote();
            $coef = $grade->getCoefficient();
            $totalWeighted += $note * $coef;
            $totalCoefficients += $coef;
        }

        if ($totalCoefficients === 0) {
            return null;
        }

        return round($totalWeighted / $totalCoefficients, 2);
    }

    /**
     * Vérifie si un étudiant a des notes pour une période
     */
    public function hasGradesForPeriod(int $studentId, string $academicYear, string $semester): bool
    {
        $count = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.student = :studentId')
            ->andWhere('g.academicYear = :year')
            ->andWhere('g.semester = :semester')
            ->setParameter('studentId', $studentId)
            ->setParameter('year', $academicYear)
            ->setParameter('semester', $semester)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
