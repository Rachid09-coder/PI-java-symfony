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
     * Normalise l'année académique pour comparaison (2025/2026 et 2025-2026 sont équivalents).
     */
    private function normalizeAcademicYear(string $year): string
    {
        return str_replace('/', '-', trim($year));
    }

    /**
     * Vérifie si une année d'examen correspond à l'année demandée (formats / ou -).
     */
    private function academicYearMatches(?string $examYear, string $requestedYear): bool
    {
        if ($examYear === null || $examYear === '') {
            return false;
        }
        return $this->normalizeAcademicYear($examYear) === $this->normalizeAcademicYear($requestedYear);
    }

    /**
     * Récupère les notes d'un étudiant à partir des examens corrigés par le professeur (ExamSubmission.grade).
     * Calcul: CC×10% + DS×20% + Exam×70% si catégories renseignées ; sinon note = note de l'examen.
     */
    public function getStudentGradesData(int $studentId, string $academicYear, int $semester): array
    {
        $yearNorm = $this->normalizeAcademicYear($academicYear);

        // 1) Examens de la période (année : 2025/2026 ou 2025-2026)
        $qb = $this->examRepository->createQueryBuilder('e')
            ->andWhere('e.semester = :semester')
            ->setParameter('semester', $semester);
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->eq('e.academicYear', ':year1'),
            $qb->expr()->eq('e.academicYear', ':year2')
        ))->setParameter('year1', $academicYear)->setParameter('year2', $yearNorm);

        // D'abord avec catégorie CC/DS/Exam
        $exams = (clone $qb)
            ->andWhere('e.gradeCategory IS NOT NULL')
            ->getQuery()
            ->getResult();

        // 2) Si aucun : récupérer les examens corrigés (submissions avec note) pour cet étudiant / période
        if (empty($exams)) {
            $exams = $this->findExamsWithGradesForStudent($studentId, $academicYear, $semester);
        }

        // Organiser par module/cours
        $moduleGrades = [];

        foreach ($exams as $exam) {
            $moduleName = $exam->getEffectiveModuleName();
            $coefficient = $exam->getEffectiveCoefficient() ?? 1;
            $category = $exam->getGradeCategory();

            if (!$moduleName) {
                $moduleName = $exam->getTitle() ?: ('Examen #' . $exam->getId());
            }

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

            if ($category !== null) {
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
            } else {
                // Examen sans catégorie : note unique (corrigée par le prof) → mise en "exam" (70%)
                if ($grade !== null) {
                    $moduleGrades[$moduleName]['exam_grade'] = $grade;
                }
            }
        }

        // Calculer les notes finales pour chaque module
        $gradesData = [];
        $totalWeighted = 0;
        $totalCoefficients = 0;

        foreach ($moduleGrades as $module) {
            $ccAverage = !empty($module['cc_grades'])
                ? array_sum($module['cc_grades']) / count($module['cc_grades'])
                : 0;
            $dsNote = $module['ds_grade'] ?? 0;
            $examNote = $module['exam_grade'] ?? 0;

            // Note finale : CC×10% + DS×20% + Exam×70%, sauf si une seule note (examen corrigé sans catégorie) → note = note du prof
            $hasBreakdown = !empty($module['cc_grades']) || $module['ds_grade'] !== null || $module['exam_grade'] !== null;
            $singleGrade = ($ccAverage > 0 && $dsNote == 0 && $examNote == 0)
                || ($dsNote > 0 && empty($module['cc_grades']) && $examNote == 0)
                || ($examNote > 0 && empty($module['cc_grades']) && $dsNote == 0);
            if ($singleGrade && ($examNote > 0 || $dsNote > 0 || $ccAverage > 0)) {
                $finalNote = round($examNote > 0 ? $examNote : ($dsNote > 0 ? $dsNote : $ccAverage), 2);
            } else {
                $finalNote = ($ccAverage * 0.10) + ($dsNote * 0.20) + ($examNote * 0.70);
                $finalNote = round($finalNote, 2);
            }

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

        // Moyenne simple = somme des notes / nombre de matières (pour affichage bulletin)
        $notesOnly = array_column($gradesData, 'note');
        $simpleAverage = count($notesOnly) > 0 ? round(array_sum($notesOnly) / count($notesOnly), 2) : 0.0;

        $rank = $this->calculateProvisionalRank($studentId, $academicYear, $semester, $simpleAverage);

        return [
            'grades' => $gradesData,
            'average' => $average,
            'simpleAverage' => $simpleAverage,
            'mention' => $this->computeMention($simpleAverage),
            'rank' => $rank,
        ];
    }

    /**
     * Récupère les examens pour lesquels l'étudiant a une soumission avec une note (examens corrigés par le prof),
     * et dont l'année/semestre correspondent (année normalisée 2025/2026 = 2025-2026).
     *
     * @return Exam[]
     */
    private function findExamsWithGradesForStudent(int $studentId, string $academicYear, int $semester): array
    {
        $yearNorm = $this->normalizeAcademicYear($academicYear);
        $submissions = $this->submissionRepository->createQueryBuilder('s')
            ->innerJoin('s.exam', 'e')
            ->where('s.student = :studentId')
            ->andWhere('s.grade IS NOT NULL')
            ->andWhere('e.semester = :semester')
            ->andWhere('e.academicYear IN (:years)')
            ->setParameter('studentId', $studentId)
            ->setParameter('semester', $semester)
            ->setParameter('years', [$academicYear, $yearNorm])
            ->getQuery()
            ->getResult();

        $exams = [];
        foreach ($submissions as $submission) {
            $exam = $submission->getExam();
            if ($exam && $this->academicYearMatches($exam->getAcademicYear(), $academicYear)) {
                $exams[$exam->getId()] = $exam;
            }
        }
        return array_values($exams);
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
