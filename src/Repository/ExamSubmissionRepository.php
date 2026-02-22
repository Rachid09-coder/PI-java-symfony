<?php

namespace App\Repository;

use App\Entity\ExamSubmission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExamSubmission>
 *
 * @method ExamSubmission|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExamSubmission|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExamSubmission[]    findAll()
 * @method ExamSubmission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExamSubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExamSubmission::class);
    }
}
