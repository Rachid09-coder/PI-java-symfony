<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $entityType = null;

    #[ORM\Column]
    private ?int $entityId = null;

    #[ORM\Column(length: 50)]
    private ?string $action = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $performedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $performedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $details = null;

    public function __construct()
    {
        $this->performedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEntityType(): ?string { return $this->entityType; }
    public function setEntityType(string $entityType): self { $this->entityType = $entityType; return $this; }

    public function getEntityId(): ?int { return $this->entityId; }
    public function setEntityId(int $entityId): self { $this->entityId = $entityId; return $this; }

    public function getAction(): ?string { return $this->action; }
    public function setAction(string $action): self { $this->action = $action; return $this; }

    public function getPerformedBy(): ?User { return $this->performedBy; }
    public function setPerformedBy(?User $user): self { $this->performedBy = $user; return $this; }

    public function getPerformedAt(): ?\DateTimeImmutable { return $this->performedAt; }
    public function setPerformedAt(\DateTimeImmutable $performedAt): self { $this->performedAt = $performedAt; return $this; }

    public function getDetails(): ?array { return $this->details; }
    public function setDetails(?array $details): self { $this->details = $details; return $this; }
}
