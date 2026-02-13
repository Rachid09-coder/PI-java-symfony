<?php

namespace App\Entity;

use App\Repository\BulletinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Certification;
use App\Entity\ReportCardLine;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BulletinRepository::class)]
class Bulletin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'étudiant est obligatoire.")]
    private ?User $student = null;

    #[ORM\Column(length: 9)]
    #[Assert\NotBlank(message: "L'année académique est obligatoire.")]
    #[Assert\Regex(
        pattern: '/^\d{4}\/\d{4}$/',
        message: "L'année académique doit être au format 2025/2026."
    )]
    private ?string $academicYear = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le semestre est obligatoire.")]
    #[Assert\Choice(
        choices: ['Semestre 1', 'Semestre 2', 'Annuel'],
        message: "Le semestre doit être Semestre 1, Semestre 2 ou Annuel."
    )]
    private ?string $semester = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La moyenne doit être comprise entre 0 et 20."
    )]
    private ?float $average = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(
        choices: ['Brouillon', 'Vérifié', 'Validé', 'Publié'],
        message: "Le statut doit être Brouillon, Vérifié, Validé ou Publié."
    )]
    private ?string $status = null;

    // --- Nouveaux champs ---

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: "La mention est obligatoire.")]
    private ?string $mention = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Le rang est obligatoire.")]
    private ?int $classRank = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hmacHash = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column(length: 30, unique: true, nullable: true)]
    private ?string $verificationCode = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validatedBy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $publishedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $revocationReason = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(\DateTimeImmutable::class)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(
        mappedBy: 'bulletin',
        targetEntity: Certification::class,
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $certifications;

    #[ORM\OneToMany(
        mappedBy: 'bulletin',
        targetEntity: ReportCardLine::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $reportCardLines;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'Brouillon';
        $this->certifications = new ArrayCollection();
        $this->reportCardLines = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getStudent(): ?User { return $this->student; }
    public function setStudent(User $student): self { $this->student = $student; return $this; }

    public function getAcademicYear(): ?string { return $this->academicYear; }
    public function setAcademicYear(string $academicYear): self { $this->academicYear = $academicYear; return $this; }

    public function getSemester(): ?string { return $this->semester; }
    public function setSemester(string $semester): self { $this->semester = $semester; return $this; }

    public function getAverage(): ?float { return $this->average; }
    public function setAverage(float $average): self { $this->average = $average; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getMention(): ?string { return $this->mention; }
    public function setMention(?string $mention): self { $this->mention = $mention; return $this; }

    public function getClassRank(): ?int { return $this->classRank; }
    public function setClassRank(?int $classRank): self { $this->classRank = $classRank; return $this; }

    public function getHmacHash(): ?string { return $this->hmacHash; }
    public function setHmacHash(?string $hmacHash): self { $this->hmacHash = $hmacHash; return $this; }

    public function getPdfPath(): ?string { return $this->pdfPath; }
    public function setPdfPath(?string $pdfPath): self { $this->pdfPath = $pdfPath; return $this; }

    public function getVerificationCode(): ?string { return $this->verificationCode; }
    public function setVerificationCode(?string $verificationCode): self { $this->verificationCode = $verificationCode; return $this; }

    public function getValidatedBy(): ?User { return $this->validatedBy; }
    public function setValidatedBy(?User $user): self { $this->validatedBy = $user; return $this; }

    public function getPublishedBy(): ?User { return $this->publishedBy; }
    public function setPublishedBy(?User $user): self { $this->publishedBy = $user; return $this; }

    public function getValidatedAt(): ?\DateTimeImmutable { return $this->validatedAt; }
    public function setValidatedAt(?\DateTimeImmutable $validatedAt): self { $this->validatedAt = $validatedAt; return $this; }

    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self { $this->publishedAt = $publishedAt; return $this; }

    public function getRevokedAt(): ?\DateTimeImmutable { return $this->revokedAt; }
    public function setRevokedAt(?\DateTimeImmutable $revokedAt): self { $this->revokedAt = $revokedAt; return $this; }

    public function getRevocationReason(): ?string { return $this->revocationReason; }
    public function setRevocationReason(?string $revocationReason): self { $this->revocationReason = $revocationReason; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    /** @return Collection<int, Certification> */
    public function getCertifications(): Collection { return $this->certifications; }

    public function addCertification(Certification $certification): self
    {
        if (!$this->certifications->contains($certification)) {
            $this->certifications->add($certification);
            $certification->setBulletin($this);
        }
        return $this;
    }

    public function removeCertification(Certification $certification): self
    {
        if ($this->certifications->removeElement($certification)) {
            if ($certification->getBulletin() === $this) {
                $certification->setBulletin(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, ReportCardLine> */
    public function getReportCardLines(): Collection { return $this->reportCardLines; }

    public function addReportCardLine(ReportCardLine $line): self
    {
        if (!$this->reportCardLines->contains($line)) {
            $this->reportCardLines->add($line);
            $line->setBulletin($this);
        }
        return $this;
    }

    public function removeReportCardLine(ReportCardLine $line): self
    {
        if ($this->reportCardLines->removeElement($line)) {
            if ($line->getBulletin() === $this) {
                $line->setBulletin(null);
            }
        }
        return $this;
    }

    /**
     * Calcule la moyenne pondérée à partir des lignes du bulletin
     */
    public function computeAverage(): float
    {
        $totalCoef = 0;
        $totalWeighted = 0;
        foreach ($this->reportCardLines as $line) {
            $totalCoef += $line->getCoefficient();
            $totalWeighted += $line->getNote() * $line->getCoefficient();
        }
        return $totalCoef > 0 ? round($totalWeighted / $totalCoef, 2) : 0;
    }

    /**
     * Détermine la mention à partir de la moyenne
     */
    public function computeMention(): ?string
    {
        $avg = $this->average;
        if ($avg === null) return null;
        if ($avg >= 16) return 'Très Bien';
        if ($avg >= 14) return 'Bien';
        if ($avg >= 12) return 'Assez Bien';
        if ($avg >= 10) return 'Passable';
        return 'Insuffisant';
    }

    public function isPublished(): bool
    {
        return $this->status === 'Publié';
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    #[Assert\Callback]
    public function validateAcademicYear(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if (!$this->academicYear) return;

        if (!preg_match('/^(\d{4})\/(\d{4})$/', $this->academicYear, $matches)) {
            $context->buildViolation("Le format de l'année académique doit être AAAA/AAAA (ex: 2025/2026).")
                ->atPath('academicYear')
                ->addViolation();
            return;
        }

        $year1 = (int)$matches[1];
        $year2 = (int)$matches[2];

        if ($year2 !== $year1 + 1) {
            $context->buildViolation("L'année académique est invalide. L'écart doit être d'un an exactement (ex: 2025/2026).")
                ->atPath('academicYear')
                ->addViolation();
        }

        $currentYear = 2026; // Based on system metadata
        if ($year1 > $currentYear) {
            $context->buildViolation("L'année académique ne peut pas être dans le futur (limite: $currentYear/" . ($currentYear + 1) . ").")
                ->atPath('academicYear')
                ->addViolation();
        }
    }
}