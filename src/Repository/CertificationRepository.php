<?php

namespace App\Repository;

use App\Entity\Certification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Certification>
 */
class CertificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Certification::class);
    }

    /**
     * Recherche et tri des certifications
     */
    public function searchAndSort(?string $search = null, string $sortBy = 'issuedAt', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.student', 's')
            ->addSelect('s');

        // Recherche par nom, prénom ou email de l'étudiant, ou par numéro unique
        if ($search) {
            $qb->andWhere('s.name LIKE :search OR s.prenom LIKE :search OR s.email LIKE :search OR c.uniqueNumber LIKE :search OR c.typeLabel LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Tri dynamique
        $validSortFields = ['issuedAt', 'typeLabel', 'status', 'uniqueNumber', 'studentName'];
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'issuedAt';
        }

        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        if ($sortBy === 'studentName') {
            $qb->orderBy('s.name', $sortOrder)
               ->addOrderBy('s.prenom', $sortOrder);
        } else {
            $qb->orderBy('c.' . $sortBy, $sortOrder);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les certifications d'un étudiant
     */
    public function findByStudentId(int $studentId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.student = :studentId')
            ->setParameter('studentId', $studentId)
            ->orderBy('c.issuedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
