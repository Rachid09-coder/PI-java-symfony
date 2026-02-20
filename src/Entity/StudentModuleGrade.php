<?php

namespace App\Entity;

use App\Repository\StudentModuleGradeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StudentModuleGradeRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_student_module_semester', columns: ['student_id', 'module_name', 'academic_year', 'semester'])]
class StudentModuleGrade
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom du module est obligatoire.")]
    private ?string $moduleName = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private ?string $academicYear = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    private ?string $semester = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 20)]
    private ?float $noteCC = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 20)]
    private ?float $noteDS = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 20)]
    private ?float $noteExam = null;

    #[ORM\Column(type: 'float')]
    #[Assert\Positive]
    private float $coefficient = 1.0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): self
    {
        $this->student = $student;
        return $this;
    }

    public function getModuleName(): ?string
    {
        return $this->moduleName;
    }

    public function setModuleName(string $moduleName): self
    {
        $this->moduleName = $moduleName;
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

    public function getNoteCC(): ?float
    {
        return $this->noteCC;
    }

    public function setNoteCC(?float $noteCC): self
    {
        $this->noteCC = $noteCC;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getNoteDS(): ?float
    {
        return $this->noteDS;
    }

    public function setNoteDS(?float $noteDS): self
    {
        $this->noteDS = $noteDS;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getNoteExam(): ?float
    {
        return $this->noteExam;
    }

    public function setNoteExam(?float $noteExam): self
    {
        $this->noteExam = $noteExam;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCoefficient(): float
    {
        return $this->coefficient;
    }

    public function setCoefficient(float $coefficient): self
    {
        $this->coefficient = $coefficient;
        return $this;
    }

    /**
     * Calcule la note pondérée: CC (10%) + DS (20%) + Exam (70%)
     */
    public function getCalculatedNote(): float
    {
        $cc = $this->noteCC ?? 0;
        $ds = $this->noteDS ?? 0;
        $exam = $this->noteExam ?? 0;
        
        return round(($cc * 0.10) + ($ds * 0.20) + ($exam * 0.70), 2);
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
