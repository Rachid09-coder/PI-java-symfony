<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return Category[]
     */
    public function findByFilters(array $filters = [], string $sortBy = 'id', string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('c');

        if (!empty($filters['search'])) {
            $qb->andWhere('c.name LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (in_array($sortBy, ['name', 'description', 'id'])) {
            $qb->orderBy('c.' . $sortBy, $direction);
        } else {
            $qb->orderBy('c.id', $direction);
        }

        return $qb->getQuery()->getResult();
    }
}
