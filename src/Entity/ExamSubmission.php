<?php

namespace App\Entity;

use App\Repository\ExamSubmissionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Exam;

#[ORM\Entity(repositoryClass: ExamSubmissionRepository::class)]
class ExamSubmission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

   #[ORM\ManyToOne(inversedBy: 'submissions')]
#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
private ?Exam $exam = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(nullable: true)]
    private ?float $grade = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isPassed = null;

   #[ORM\Column(type: 'datetime')]
private ?\DateTimeInterface $submittedAt = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;
        return $this;
    }

    public function getExam(): ?Exam
    {
        return $this->exam;
    }

    public function setExam(?Exam $exam): static
    {
        $this->exam = $exam;
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

    public function getGrade(): ?float
    {
        return $this->grade;
    }

    public function setGrade(?float $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function isPassed(): ?bool
    {
        return $this->isPassed;
    }

    public function setIsPassed(?bool $isPassed): static
    {
        $this->isPassed = $isPassed;
        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeInterface $submittedAt): static
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }
}
