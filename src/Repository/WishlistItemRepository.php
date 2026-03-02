<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\WishlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WishlistItem>
 */
class WishlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistItem::class);
    }

    /**
     * @return WishlistItem[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndProduct(User $user, Product $product): ?WishlistItem
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.product = :product')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return int[] product ids
     */
    public function getProductIdsByUser(User $user): array
    {
        $items = $this->createQueryBuilder('w')
            ->select('IDENTITY(w.product)')
            ->andWhere('w.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();
        return array_map('intval', $items);
    }
}
