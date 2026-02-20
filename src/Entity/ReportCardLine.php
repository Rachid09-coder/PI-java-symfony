<?php

namespace App\Entity;

use App\Repository\ReportCardLineRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReportCardLineRepository::class)]
class ReportCardLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reportCardLines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Bulletin $bulletin = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom du module est obligatoire.")]
    private ?string $moduleName = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 20, notInRangeMessage: "La note CC doit être entre {{ min }} et {{ max }}.")]
    private ?float $noteCC = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 20, notInRangeMessage: "La note DS doit être entre {{ min }} et {{ max }}.")]
    private ?float $noteDS = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 20, notInRangeMessage: "La note Exam doit être entre {{ min }} et {{ max }}.")]
    private ?float $noteExam = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: "La note est obligatoire.")]
    #[Assert\Range(min: 0, max: 20, notInRangeMessage: "La note doit être entre {{ min }} et {{ max }}.")]
    private ?float $note = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?float $coefficient = 1.0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $teacherComment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Grade $grade = null;

    public function getId(): ?int { return $this->id; }

    public function getBulletin(): ?Bulletin { return $this->bulletin; }
    public function setBulletin(?Bulletin $bulletin): self { $this->bulletin = $bulletin; return $this; }

    public function getModuleName(): ?string { return $this->moduleName; }
    public function setModuleName(string $moduleName): self { $this->moduleName = $moduleName; return $this; }

    public function getNoteCC(): ?float { return $this->noteCC; }
    public function setNoteCC(?float $noteCC): self { 
        $this->noteCC = $noteCC; 
        $this->calculateNote();
        return $this; 
    }

    public function getNoteDS(): ?float { return $this->noteDS; }
    public function setNoteDS(?float $noteDS): self { 
        $this->noteDS = $noteDS; 
        $this->calculateNote();
        return $this; 
    }

    public function getNoteExam(): ?float { return $this->noteExam; }
    public function setNoteExam(?float $noteExam): self { 
        $this->noteExam = $noteExam; 
        $this->calculateNote();
        return $this; 
    }

    /**
     * Calcule la note pondérée: CC (10%) + DS (20%) + Exam (70%)
     */
    public function calculateNote(): void
    {
        $cc = $this->noteCC ?? 0;
        $ds = $this->noteDS ?? 0;
        $exam = $this->noteExam ?? 0;
        
        $this->note = round(($cc * 0.10) + ($ds * 0.20) + ($exam * 0.70), 2);
    }

    public function getNote(): ?float { return $this->note; }
    public function setNote(float $note): self { $this->note = $note; return $this; }

    public function getCoefficient(): ?float { return $this->coefficient; }
    public function setCoefficient(float $coefficient): self { $this->coefficient = $coefficient; return $this; }

    public function getTeacherComment(): ?string { return $this->teacherComment; }
    public function setTeacherComment(?string $teacherComment): self { $this->teacherComment = $teacherComment; return $this; }

    public function getGrade(): ?Grade { return $this->grade; }
    public function setGrade(?Grade $grade): self { $this->grade = $grade; return $this; }
}
