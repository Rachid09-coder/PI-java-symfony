<?php

namespace App\Repository;

use App\Entity\Grade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Grade::class);
    }

    /** @return Grade[] */
    public function findByStudentAndPeriod(int $studentId, string $academicYear, string $semester): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.student = :studentId')
            ->andWhere('g.academicYear = :year')
            ->andWhere('g.semester = :semester')
            ->setParameter('studentId', $studentId)
            ->setParameter('year', $academicYear)
            ->setParameter('semester', $semester)
            ->orderBy('g.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
