<?php

namespace App\Repository;

use App\Entity\MetierAvance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MetierAvance>
 *
 * @method MetierAvance|null find($id, $lockMode = null, $lockVersion = null)
 * @method MetierAvance|null findOneBy(array $criteria, array $orderBy = null)
 * @method MetierAvance[]    findAll()
 * @method MetierAvance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MetierAvanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetierAvance::class);
    }
}
