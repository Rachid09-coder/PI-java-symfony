<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findByFilters(array $filters = [], string $sortBy = 'id', string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('p.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Allow sorting by product fields or category name
        if ($sortBy === 'category') {
            $qb->orderBy('c.name', $direction);
        } elseif (in_array($sortBy, ['name', 'price', 'stock'])) {
            $qb->orderBy('p.' . $sortBy, $direction);
        } else {
            $qb->orderBy('p.id', $direction);
        }

        return $qb->getQuery()->getResult();
    }
}
