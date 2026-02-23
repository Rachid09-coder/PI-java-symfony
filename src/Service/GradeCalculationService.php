<?php

namespace App\Service;

use App\Entity\Bulletin;
use App\Entity\ReportCardLine;
use App\Entity\User;
use App\Entity\Exam;
use App\Entity\ExamSubmission;
use App\Repository\ExamRepository;
use App\Repository\ExamSubmissionRepository;
use Doctrine\ORM\EntityManagerInterface;

class GradeCalculationService
{
    public function __construct(
        private ExamRepository $examRepository,
        private ExamSubmissionRepository $submissionRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Récupère les notes d'un étudiant à partir des examens/submissions pour une période
     * Calcul: CC (moyenne des Quiz/QCM) = 10%, DS = 20%, Exam = 70%
     */
    public function getStudentGradesData(int $studentId, string $academicYear, int $semester): array
    {
        // Récupérer tous les examens de la période avec leurs soumissions
        $exams = $this->examRepository->createQueryBuilder('e')
            ->where('e.academicYear = :year')
            ->andWhere('e.semester = :semester')
            ->andWhere('e.gradeCategory IS NOT NULL')
            ->setParameter('year', $academicYear)
            ->setParameter('semester', $semester)
            ->getQuery()
            ->getResult();

        // Organiser par module/cours
        $moduleGrades = [];

        foreach ($exams as $exam) {
            $moduleName = $exam->getEffectiveModuleName();
            $coefficient = $exam->getEffectiveCoefficient() ?? 1;
            $category = $exam->getGradeCategory();

            if (!$moduleName) {
                continue;
            }

            // Trouver la soumission de l'étudiant pour cet examen
            $submission = $this->submissionRepository->findOneBy([
                'exam' => $exam,
                'student' => $studentId
            ]);

            $grade = $submission?->getGrade();

            if (!isset($moduleGrades[$moduleName])) {
                $moduleGrades[$moduleName] = [
                    'moduleName' => $moduleName,
                    'coefficient' => $coefficient,
                    'cc_grades' => [],
                    'ds_grade' => null,
                    'exam_grade' => null,
                ];
            }

            // Affecter la note selon la catégorie
            switch ($category) {
                case 'cc':
                    if ($grade !== null) {
                        $moduleGrades[$moduleName]['cc_grades'][] = $grade;
                    }
                    break;
                case 'ds':
                    $moduleGrades[$moduleName]['ds_grade'] = $grade;
                    break;
                case 'exam':
                    $moduleGrades[$moduleName]['exam_grade'] = $grade;
                    break;
            }
        }

        // Calculer les notes finales pour chaque module
        $gradesData = [];
        $totalWeighted = 0;
        $totalCoefficients = 0;

        foreach ($moduleGrades as $module) {
            // Calculer la moyenne CC (10%)
            $ccAverage = !empty($module['cc_grades']) 
                ? array_sum($module['cc_grades']) / count($module['cc_grades']) 
                : 0;
            
            $dsNote = $module['ds_grade'] ?? 0;
            $examNote = $module['exam_grade'] ?? 0;
            
            // Note finale: CC*0.10 + DS*0.20 + Exam*0.70
            $finalNote = ($ccAverage * 0.10) + ($dsNote * 0.20) + ($examNote * 0.70);
            $finalNote = round($finalNote, 2);
            
            $coef = $module['coefficient'];
            
            $gradesData[] = [
                'moduleName' => $module['moduleName'],
                'noteCC' => round($ccAverage, 2),
                'noteDS' => $dsNote,
                'noteExam' => $examNote,
                'note' => $finalNote,
                'coefficient' => $coef,
            ];

            $totalWeighted += $finalNote * $coef;
            $totalCoefficients += $coef;
        }

        $average = $totalCoefficients > 0 ? round($totalWeighted / $totalCoefficients, 2) : 0;

        // Calculer le rang provisoire (basé sur les bulletins existants de la même période)
        $rank = $this->calculateProvisionalRank($studentId, $academicYear, $semester, $average);

        return [
            'grades' => $gradesData,
            'average' => $average,
            'mention' => $this->computeMention($average),
            'rank' => $rank,
        ];
    }

    /**
     * Calcule le rang provisoire d'un étudiant pour une période donnée
     * Retourne un entier (position dans le classement)
     */
    public function calculateProvisionalRank(int $studentId, string $academicYear, int $semester, float $average): int
    {
        // Compter les bulletins avec une moyenne supérieure
        $higherCount = $this->em->getRepository(Bulletin::class)->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.academicYear = :year')
            ->andWhere('b.semester = :semester')
            ->andWhere('b.average > :avg')
            ->setParameter('year', $academicYear)
            ->setParameter('semester', $semester)
            ->setParameter('avg', $average)
            ->getQuery()
            ->getSingleScalarResult();

        // Le rang = nombre de bulletins avec moyenne supérieure + 1
        return (int)$higherCount + 1;
    }

    /**
     * Génère les lignes de bulletin à partir des notes des examens
     */
    public function generateReportCardLines(Bulletin $bulletin): void
    {
        $student = $bulletin->getStudent();
        $academicYear = $bulletin->getAcademicYear();
        $semester = $bulletin->getSemester();

        if (!$student || !$academicYear || !$semester) {
            return;
        }

        $data = $this->getStudentGradesData(
            $student->getId(),
            $academicYear,
            (int) $semester
        );

        foreach ($data['grades'] as $gradeData) {
            $line = new ReportCardLine();
            $line->setBulletin($bulletin);
            $line->setModuleName($gradeData['moduleName']);
            $line->setNoteCC($gradeData['noteCC']);
            $line->setNoteDS($gradeData['noteDS']);
            $line->setNoteExam($gradeData['noteExam']);
            $line->setCoefficient($gradeData['coefficient']);
            $line->setNote($gradeData['note']);
            
            $bulletin->addReportCardLine($line);
        }

        // Recalculer la moyenne du bulletin
        $this->recalculateBulletinAverage($bulletin);
    }

    /**
     * Recalcule la moyenne générale d'un bulletin
     */
    public function recalculateBulletinAverage(Bulletin $bulletin): void
    {
        $lines = $bulletin->getReportCardLines();
        
        if ($lines->isEmpty()) {
            $bulletin->setAverage(0);
            return;
        }

        $totalWeighted = 0;
        $totalCoefficients = 0;

        foreach ($lines as $line) {
            $totalWeighted += $line->getNote() * $line->getCoefficient();
            $totalCoefficients += $line->getCoefficient();
        }

        $average = $totalCoefficients > 0 ? round($totalWeighted / $totalCoefficients, 2) : 0;
        $bulletin->setAverage($average);
        $bulletin->setMention($this->computeMention($average));
    }

    /**
     * Calcule la mention selon la moyenne
     */
    public function computeMention(float $average): string
    {
        if ($average >= 16) return 'Très Bien';
        if ($average >= 14) return 'Bien';
        if ($average >= 12) return 'Assez Bien';
        if ($average >= 10) return 'Passable';
        return 'Insuffisant';
    }

    /**
     * Calcule et met à jour les rangs de tous les bulletins d'une période
     */
    public function calculateRanks(string $academicYear, int $semester): void
    {
        $bulletins = $this->em->getRepository(Bulletin::class)->createQueryBuilder('b')
            ->where('b.academicYear = :year')
            ->andWhere('b.semester = :semester')
            ->andWhere('b.average IS NOT NULL')
            ->orderBy('b.average', 'DESC')
            ->setParameter('year', $academicYear)
            ->setParameter('semester', $semester)
            ->getQuery()
            ->getResult();

        $rank = 1;
        $totalStudents = count($bulletins);
        
        foreach ($bulletins as $bulletin) {
            $bulletin->setClassRank($rank);
            $rank++;
        }

        $this->em->flush();
    }
}
