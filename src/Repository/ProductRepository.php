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
     * @param array{search?: string, category?: int} $filters
     * @param string $sortBy id|name|category|price|stock
     * @param string $direction ASC|DESC
     * @return Product[]
     */
    public function findFilteredAndSorted(array $filters = [], string $sortBy = 'id', string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c');

        if (!empty($filters['search'])) {
            $qb->andWhere('p.name LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }
        if (!empty($filters['category'])) {
            $qb->andWhere('p.category = :catId')
                ->setParameter('catId', (int) $filters['category']);
        }

        $allowedSort = ['id', 'name', 'price', 'stock', 'category'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }
        if ($sortBy === 'category') {
            $qb->orderBy('c.name', $direction === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('p.' . $sortBy, $direction === 'DESC' ? 'DESC' : 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Produits recommandés pour la page boutique (exclut les ids déjà en wishlist).
     *
     * @param int $limit
     * @param int[] $excludeProductIds
     * @return Product[]
     */
    public function findRecommendedForShop(int $limit, array $excludeProductIds = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.stock > 0')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit * 2);
        if ($excludeProductIds !== []) {
            $qb->andWhere('p.id NOT IN (:ids)')->setParameter('ids', $excludeProductIds);
        }
        $products = $qb->getQuery()->getResult();
        shuffle($products);
        return array_slice($products, 0, $limit);
    }

    /**
     * Produits recommandés pour « Complétez votre panier ».
     *
     * @return Product[]
     */
    public function findRecommendedForCart(int $limit): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.stock > 0')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit * 2);
        $products = $qb->getQuery()->getResult();
        shuffle($products);
        return array_slice($products, 0, $limit);
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
