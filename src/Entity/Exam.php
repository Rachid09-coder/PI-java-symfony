<?php

namespace App\Entity;

use App\Repository\ExamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamRepository::class)]
class Exam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'exam', targetEntity: Question::class, orphanRemoval: true)]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'exam', targetEntity: ExamSubmission::class, orphanRemoval: true)]
    private Collection $submissions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->submissions = new ArrayCollection();
    }

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalLink = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $moduleName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $gradeCategory = null; // 'cc', 'ds', 'exam'

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $academicYear = null; // ex: "2024-2025"

    #[ORM\Column(nullable: true)]
    private ?int $semester = null; // 1 ou 2

    #[ORM\Column(nullable: true)]
    private ?float $coefficient = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'exams')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Course $course = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getExternalLink(): ?string
    {
        return $this->externalLink;
    }

    public function setExternalLink(?string $externalLink): static
    {
        $this->externalLink = $externalLink;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setExam($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getExam() === $this) {
                $question->setExam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ExamSubmission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function addSubmission(ExamSubmission $submission): static
    {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->setExam($this);
        }

        return $this;
    }

    public function removeSubmission(ExamSubmission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            // set the owning side to null (unless already changed)
            if ($submission->getExam() === $this) {
                $submission->setExam(null);
            }
        }

        return $this;
    }

    public function getModuleName(): ?string
    {
        return $this->moduleName;
    }

    public function setModuleName(?string $moduleName): static
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    public function getGradeCategory(): ?string
    {
        return $this->gradeCategory;
    }

    public function setGradeCategory(?string $gradeCategory): static
    {
        $this->gradeCategory = $gradeCategory;

        return $this;
    }

    public function getAcademicYear(): ?string
    {
        return $this->academicYear;
    }

    public function setAcademicYear(?string $academicYear): static
    {
        $this->academicYear = $academicYear;

        return $this;
    }

    public function getSemester(): ?int
    {
        return $this->semester;
    }

    public function setSemester(?int $semester): static
    {
        $this->semester = $semester;

        return $this;
    }

    public function getCoefficient(): ?float
    {
        return $this->coefficient;
    }

    public function setCoefficient(?float $coefficient): static
    {
        $this->coefficient = $coefficient;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Retourne le coefficient effectif (du cours lié ou du champ local)
     */
    public function getEffectiveCoefficient(): ?float
    {
        if ($this->course && $this->course->getCoefficient()) {
            return $this->course->getCoefficient();
        }
        return $this->coefficient;
    }

    /**
     * Retourne le nom du module/matière effectif (du cours lié ou du champ local)
     */
    public function getEffectiveModuleName(): ?string
    {
        if ($this->course) {
            return $this->course->getTitle();
        }
        return $this->moduleName;
    }
}
