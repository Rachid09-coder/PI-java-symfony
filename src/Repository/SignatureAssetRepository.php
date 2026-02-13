<?php

namespace App\Repository;

use App\Entity\SignatureAsset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SignatureAssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignatureAsset::class);
    }

    /** @return SignatureAsset[] */
    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type], ['createdAt' => 'DESC']);
    }
}
