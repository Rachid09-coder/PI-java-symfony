<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * @return Course[]
     */
    public function findAllOrderedByTitle(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
