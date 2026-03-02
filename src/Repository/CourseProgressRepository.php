<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\CourseProgress;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseProgress>
 */
class CourseProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseProgress::class);
    }

    public function findOneByStudentAndCourse(User $student, Course $course): ?CourseProgress
    {
        return $this->createQueryBuilder('cp')
            ->andWhere('cp.student = :student')
            ->andWhere('cp.course = :course')
            ->setParameter('student', $student)
            ->setParameter('course', $course)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return CourseProgress[] */
    public function findByStudent(User $student): array
    {
        return $this->createQueryBuilder('cp')
            ->andWhere('cp.student = :student')
            ->setParameter('student', $student)
            ->orderBy('cp.lastAccessedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(CourseProgress $progress): void
    {
        $this->getEntityManager()->persist($progress);
        $this->getEntityManager()->flush();
    }
}
