<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    // ── Status constants ────────────────────────────────────────
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    // ── Primary key ─────────────────────────────────────────────
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ── Relationships ────────────────────────────────────────────
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // ── Order data ───────────────────────────────────────────────
    /** Cart items: [{id, name, price}] */
    #[ORM\Column(type: Types::JSON)]
    private array $items = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    // ── EasyPost shipping fields ─────────────────────────────────
    /** EasyPost tracker ID, e.g. trk_xxxxxxxxxxxxxxxx */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackerId = null;

    /** Courier tracking number, e.g. EZ1000000001 */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $trackingNumber = null;

    /** Carrier name as returned by EasyPost, e.g. USPS */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $carrier = null;

    /** EasyPost tracker status: pre_transit | in_transit | out_for_delivery | delivered | error | unknown */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $shippingStatus = null;

    /** Granular status detail, e.g. "address_not_found" */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statusDetail = null;

    /** Public EasyPost tracking URL */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $publicUrl = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $shippingUpdatedAt = null;

    // ── Timestamps ───────────────────────────────────────────────
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ── Getters & Setters ────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getTrackerId(): ?string
    {
        return $this->trackerId;
    }

    public function setTrackerId(?string $trackerId): static
    {
        $this->trackerId = $trackerId;
        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): static
    {
        $this->carrier = $carrier;
        return $this;
    }

    public function getShippingStatus(): ?string
    {
        return $this->shippingStatus;
    }

    public function setShippingStatus(?string $shippingStatus): static
    {
        $this->shippingStatus = $shippingStatus;
        return $this;
    }

    public function getStatusDetail(): ?string
    {
        return $this->statusDetail;
    }

    public function setStatusDetail(?string $statusDetail): static
    {
        $this->statusDetail = $statusDetail;
        return $this;
    }

    public function getPublicUrl(): ?string
    {
        return $this->publicUrl;
    }

    public function setPublicUrl(?string $publicUrl): static
    {
        $this->publicUrl = $publicUrl;
        return $this;
    }

    public function getShippingUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->shippingUpdatedAt;
    }

    public function setShippingUpdatedAt(?\DateTimeImmutable $shippingUpdatedAt): static
    {
        $this->shippingUpdatedAt = $shippingUpdatedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
