<?php

namespace App\Entity;

use App\Repository\ExamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\ExamSubmission;

#[ORM\Entity(repositoryClass: ExamRepository::class)]
class Exam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null; // pdf | link | word

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalLink = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    /*
    ======================================================
    🔴 VERY IMPORTANT PART (RELATION WITH SUBMISSIONS)
    ======================================================
    */

    #[ORM\OneToMany(mappedBy: 'exam', targetEntity: ExamSubmission::class, orphanRemoval: true, cascade: ['remove'])]
    private Collection $submissions;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
    }

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
            if ($submission->getExam() === $this) {
                $submission->setExam(null);
            }
        }

        return $this;
    }

    // ======================================================
    // Getters & Setters
    // ======================================================

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getFilePath(): ?string { return $this->filePath; }
    public function setFilePath(?string $filePath): static { $this->filePath = $filePath; return $this; }

    public function getExternalLink(): ?string { return $this->externalLink; }
    public function setExternalLink(?string $externalLink): static { $this->externalLink = $externalLink; return $this; }

    public function getDuration(): ?int { return $this->duration; }
    public function setDuration(?int $duration): static { $this->duration = $duration; return $this; }
}
