<?php

namespace App\Entity;

use App\Repository\ExamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\ExamSubmission;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExamRepository::class)]
class Exam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    // ================= VALIDATION TITRE =================
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre de l'examen est obligatoire")]
    #[Assert\Length(
        min: 4,
        max: 100,
        minMessage: "Le titre doit contenir au moins 4 caractères",
        maxMessage: "Le titre ne doit pas dépasser 100 caractères"
    )]
    private ?string $title = null;


    // ================= VALIDATION DESCRIPTION =================
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description est trop longue (1000 caractères max)"
    )]
    private ?string $description = null;


    // ================= VALIDATION TYPE =================
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le type d'examen est obligatoire")]
    #[Assert\Choice(
        choices: ['pdf', 'link', 'word'],
        message: "Type d'examen invalide (pdf, link ou word seulement)"
    )]
    private ?string $type = null; // pdf | link | word


    // ================= FICHIER =================
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;


    // ================= VALIDATION LIEN =================
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "Veuillez entrer un lien valide (https://...)")]
    private ?string $externalLink = null;


    // ================= VALIDATION DURÉE =================
    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "La durée de l'examen est obligatoire")]
    #[Assert\Positive(message: "La durée doit être positive")]
    #[Assert\LessThanOrEqual(
        value: 300,
        message: "La durée maximale autorisée est 300 minutes"
    )]
    private ?int $duration = null;


    /*
    ======================================================
    RELATION AVEC LES COPIES DES ÉTUDIANTS
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

    // ================= GETTERS & SETTERS =================

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
