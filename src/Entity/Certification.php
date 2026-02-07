<?php

namespace App\Entity;

use App\Repository\CertificationRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Bulletin;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CertificationRepository::class)]
class Certification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Étudiant pour qui la certification est émise
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'étudiant est obligatoire.")]
    private ?User $student = null;

    // Bulletin de référence
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le bulletin lié est obligatoire.")]
    private ?Bulletin $bulletin = null;

    // Type : ATTESTATION_REUSSITE, RELEVE_NOTES, CERTIFICAT...
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le type de certification est obligatoire.")]
    #[Assert\Choice(
        choices: ['ATTESTATION_REUSSITE', 'RELEVE_NOTES', 'CERTIFICAT'],
        message: "Le type doit être ATTESTATION_REUSSITE, RELEVE_NOTES ou CERTIFICAT."
    )]
    private ?string $type = null;

    // Date d'émission
    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $issuedAt = null;

    // Code de vérification unique
    #[ORM\Column(length: 30, unique: true)]
    #[Assert\NotBlank(message: "Le code de vérification est obligatoire.")]
    #[Assert\Length(
        min: 8,
        minMessage: "Le code doit contenir au moins {{ limit }} caractères."
    )]
    private ?string $verificationCode = null;

    // Chemin vers le PDF généré (optionnel)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    // Statut : ACTIVE, REVOKED
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: ['ACTIVE', 'REVOKED'],
        message: "Le statut doit être ACTIVE ou REVOKED."
    )]
    private ?string $status = null;

    public function __construct()
    {
        $this->issuedAt = new \DateTimeImmutable();
        $this->status = 'ACTIVE';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(User $student): self
    {
        $this->student = $student;
        return $this;
    }

    public function getBulletin(): ?Bulletin
    {
        return $this->bulletin;
    }

    public function setBulletin(Bulletin $bulletin): self
    {
        $this->bulletin = $bulletin;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(\DateTimeImmutable $issuedAt): self
    {
        $this->issuedAt = $issuedAt;
        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(string $verificationCode): self
    {
        $this->verificationCode = $verificationCode;
        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): self
    {
        $this->pdfPath = $pdfPath;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
}