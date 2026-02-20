<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\ForumThread;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ForumThread>
 */
class ForumThreadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumThread::class);
    }

    /**
     * @return ForumThread[]
     */
    public function findByCourseOrderByUpdated(Course $course): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.course = :course')
            ->setParameter('course', $course)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
