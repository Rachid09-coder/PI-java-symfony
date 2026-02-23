<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Finds one Order by its EasyPost tracker ID.
     */
    public function findOneByTrackerId(string $trackerId): ?Order
    {
        return $this->findOneBy(['trackerId' => $trackerId]);
    }

    /**
     * Finds all paid orders for a specific user, newest first.
     *
     * @return Order[]
     */
    public function findPaidByUser(int $userId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :uid')
            ->andWhere('o.status = :status')
            ->setParameter('uid', $userId)
            ->setParameter('status', Order::STATUS_PAID)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Order[]
     */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end, string $status = 'paid'): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->andWhere('o.createdAt >= :start')
            ->andWhere('o.createdAt <= :end')
            ->setParameter('status', $status)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
