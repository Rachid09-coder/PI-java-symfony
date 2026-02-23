<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AuditService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Enregistre une action dans le journal d'audit
     */
    public function log(
        string $entityType,
        int $entityId,
        string $action,
        ?User $user = null,
        ?array $details = null
    ): void {
        $log = new AuditLog();
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setAction($action);
        $log->setPerformedBy($user);
        $log->setDetails($details);

        $this->em->persist($log);
        $this->em->flush();
    }
}
