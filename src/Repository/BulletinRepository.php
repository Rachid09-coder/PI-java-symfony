<?php

namespace App\Repository;

use App\Entity\Bulletin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BulletinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bulletin::class);
    }

    /**
     * Recherche et tri des bulletins
     */
    public function searchAndSort(?string $search = null, string $sortBy = 'createdAt', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.student', 's')
            ->addSelect('s');

        // Recherche par nom, prénom ou email de l'étudiant
        if ($search) {
            $qb->andWhere('s.name LIKE :search OR s.prenom LIKE :search OR s.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Tri dynamique
        $validSortFields = ['createdAt', 'average', 'academicYear', 'semester', 'classRank', 'studentName'];
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'createdAt';
        }

        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        if ($sortBy === 'studentName') {
            $qb->orderBy('s.name', $sortOrder)
               ->addOrderBy('s.prenom', $sortOrder);
        } else {
            $qb->orderBy('b.' . $sortBy, $sortOrder);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les bulletins d'un étudiant
     */
    public function findByStudentId(int $studentId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.student = :studentId')
            ->setParameter('studentId', $studentId)
            ->orderBy('b.academicYear', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le rang d'un bulletin par rapport aux autres bulletins
     * de la même année académique et du même semestre
     */
    public function calculateRank(Bulletin $bulletin): int
    {
        $bulletins = $this->createQueryBuilder('b')
            ->where('b.academicYear = :year')
            ->andWhere('b.semester = :semester')
            ->setParameter('year', $bulletin->getAcademicYear())
            ->setParameter('semester', $bulletin->getSemester())
            ->orderBy('b.average', 'DESC')
            ->getQuery()
            ->getResult();

        $rank = 1;
        foreach ($bulletins as $b) {
            if ($b->getId() === $bulletin->getId()) {
                return $rank;
            }
            $rank++;
        }

        return $rank;
    }

    /**
     * Recalcule les rangs de tous les bulletins d'une année/semestre
     */
    public function recalculateAllRanks(string $academicYear, string $semester): void
    {
        $bulletins = $this->createQueryBuilder('b')
            ->where('b.academicYear = :year')
            ->andWhere('b.semester = :semester')
            ->setParameter('year', $academicYear)
            ->setParameter('semester', $semester)
            ->orderBy('b.average', 'DESC')
            ->getQuery()
            ->getResult();

        $rank = 1;
        foreach ($bulletins as $bulletin) {
            $bulletin->setClassRank($rank);
            $rank++;
        }
    }
}
