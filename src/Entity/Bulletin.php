<?php

namespace App\Entity;

use App\Repository\BulletinRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BulletinRepository::class)]
class Bulletin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Étudiant concerné
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'étudiant est obligatoire.")]
    private ?User $student = null;

    // Année académique : 2025-2026
    #[ORM\Column(length: 9)]
    #[Assert\NotBlank(message: "L'année académique est obligatoire.")]
    #[Assert\Regex(
        pattern: '/^\d{4}-\d{4}$/',
        message: "L'année académique doit être au format 2025-2026."
    )]
    private ?string $academicYear = null;

    // Semestre : S1, S2, ANNUEL
    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: "Le semestre est obligatoire.")]
    #[Assert\Choice(
        choices: ['S1', 'S2', 'ANNUEL'],
        message: "Le semestre doit être S1, S2 ou ANNUEL."
    )]
    private ?string $semester = null;

    // Moyenne générale du bulletin
    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: "La moyenne est obligatoire.")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La moyenne doit être comprise entre {{ min }} et {{ max }}."
    )]
    private ?float $average = null;

    // Statut du bulletin : DRAFT, VALIDATED, ARCHIVED
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(
        choices: ['DRAFT', 'VALIDATED', 'ARCHIVED'],
        message: "Le statut doit être DRAFT, VALIDATED ou ARCHIVED."
    )]
    private ?string $status = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(\DateTimeImmutable::class)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'DRAFT';
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

    public function getAcademicYear(): ?string
    {
        return $this->academicYear;
    }

    public function setAcademicYear(string $academicYear): self
    {
        $this->academicYear = $academicYear;

        return $this;
    }

    public function getSemester(): ?string
    {
        return $this->semester;
    }

    public function setSemester(string $semester): self
    {
        $this->semester = $semester;

        return $this;
    }

    public function getAverage(): ?float
    {
        return $this->average;
    }

    public function setAverage(float $average): self
    {
        $this->average = $average;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}