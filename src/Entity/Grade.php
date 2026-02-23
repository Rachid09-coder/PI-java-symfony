<?php

namespace App\Entity;

use App\Repository\GradeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GradeRepository::class)]
class Grade
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'étudiant est obligatoire.")]
    private ?User $student = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le module est obligatoire.")]
    private ?Module $module = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: "La note est obligatoire.")]
    #[Assert\Range(min: 0, max: 20, notInRangeMessage: "La note doit être entre {{ min }} et {{ max }}.")]
    private ?float $note = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: "Le coefficient est obligatoire.")]
    #[Assert\Positive(message: "Le coefficient doit être positif.")]
    private ?float $coefficient = 1.0;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['Normale', 'Rattrapage'], message: "Session invalide.")]
    private ?string $session = 'Normale';

    #[ORM\Column(length: 9)]
    #[Assert\NotBlank]
    private ?string $academicYear = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private ?string $semester = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getStudent(): ?User { return $this->student; }
    public function setStudent(?User $student): self { $this->student = $student; return $this; }

    public function getModule(): ?Module { return $this->module; }
    public function setModule(?Module $module): self { $this->module = $module; return $this; }

    public function getNote(): ?float { return $this->note; }
    public function setNote(float $note): self { $this->note = $note; return $this; }

    public function getCoefficient(): ?float { return $this->coefficient; }
    public function setCoefficient(float $coefficient): self { $this->coefficient = $coefficient; return $this; }

    public function getSession(): ?string { return $this->session; }
    public function setSession(string $session): self { $this->session = $session; return $this; }

    public function getAcademicYear(): ?string { return $this->academicYear; }
    public function setAcademicYear(string $academicYear): self { $this->academicYear = $academicYear; return $this; }

    public function getSemester(): ?string { return $this->semester; }
    public function setSemester(string $semester): self { $this->semester = $semester; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
