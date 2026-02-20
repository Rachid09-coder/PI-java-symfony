<?php

namespace App\DataFixtures;

use App\Entity\Exam;
use App\Entity\ExamSubmission;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixture pour créer des données de test pour les notes et bulletins
 * Exécuter avec: php bin/console doctrine:fixtures:load --group=grades --append
 */
class GradeTestFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public static function getGroups(): array
    {
        return ['grades'];
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Créer un étudiant de test s'il n'existe pas
        $studentEmail = 'etudiant.test@edusmart.com';
        $existingStudent = $manager->getRepository(User::class)->findOneBy(['email' => $studentEmail]);
        
        if (!$existingStudent) {
            $student = new User();
            $student->setEmail($studentEmail);
            $student->setName('Dupont');
            $student->setPrenom('Jean');
            $student->setRole('etudiant');
            $student->setNumtel('0612345678');
            $student->setPassword($this->passwordHasher->hashPassword($student, 'test123'));
            $manager->persist($student);
        } else {
            $student = $existingStudent;
        }

        $manager->flush();

        // 2. Définir les modules et créer les examens avec gradeCategory
        $academicYear = '2025-2026';
        $semester = 1;
        
        $modules = [
            'Mathématiques' => ['coefficient' => 3],
            'Informatique' => ['coefficient' => 4],
            'Physique' => ['coefficient' => 2],
            'Anglais' => ['coefficient' => 2],
            'Communication' => ['coefficient' => 1],
        ];

        // Notes pour chaque module: [CC, DS, Exam]
        $grades = [
            'Mathématiques' => ['cc' => [14, 15, 13], 'ds' => 16, 'exam' => 15],
            'Informatique' => ['cc' => [18, 17, 16], 'ds' => 18, 'exam' => 17],
            'Physique' => ['cc' => [12, 11], 'ds' => 13, 'exam' => 12],
            'Anglais' => ['cc' => [15, 14], 'ds' => 14, 'exam' => 15],
            'Communication' => ['cc' => [16], 'ds' => 15, 'exam' => 16],
        ];

        foreach ($modules as $moduleName => $moduleData) {
            $coefficient = $moduleData['coefficient'];
            $moduleGrades = $grades[$moduleName];

            // Créer les examens CC (Quiz/QCM)
            foreach ($moduleGrades['cc'] as $index => $ccGrade) {
                $exam = new Exam();
                $exam->setTitle("Quiz {$moduleName} " . ($index + 1));
                $exam->setDescription("Contrôle continu pour {$moduleName}");
                $exam->setType('qcm');
                $exam->setModuleName($moduleName);
                $exam->setGradeCategory('cc');
                $exam->setAcademicYear($academicYear);
                $exam->setSemester($semester);
                $exam->setCoefficient($coefficient);
                $exam->setDuration(30);
                $manager->persist($exam);

                // Créer la soumission avec la note
                $submission = new ExamSubmission();
                $submission->setStudent($student);
                $submission->setExam($exam);
                $submission->setGrade($ccGrade);
                $submission->setIsPassed($ccGrade >= 10);
                $manager->persist($submission);
            }

            // Créer l'examen DS
            $examDS = new Exam();
            $examDS->setTitle("DS {$moduleName}");
            $examDS->setDescription("Devoir surveillé pour {$moduleName}");
            $examDS->setType('pdf');
            $examDS->setModuleName($moduleName);
            $examDS->setGradeCategory('ds');
            $examDS->setAcademicYear($academicYear);
            $examDS->setSemester($semester);
            $examDS->setCoefficient($coefficient);
            $examDS->setDuration(120);
            $manager->persist($examDS);

            $submissionDS = new ExamSubmission();
            $submissionDS->setStudent($student);
            $submissionDS->setExam($examDS);
            $submissionDS->setGrade($moduleGrades['ds']);
            $submissionDS->setIsPassed($moduleGrades['ds'] >= 10);
            $manager->persist($submissionDS);

            // Créer l'examen final
            $examFinal = new Exam();
            $examFinal->setTitle("Examen Final {$moduleName}");
            $examFinal->setDescription("Examen final pour {$moduleName}");
            $examFinal->setType('pdf');
            $examFinal->setModuleName($moduleName);
            $examFinal->setGradeCategory('exam');
            $examFinal->setAcademicYear($academicYear);
            $examFinal->setSemester($semester);
            $examFinal->setCoefficient($coefficient);
            $examFinal->setDuration(180);
            $manager->persist($examFinal);

            $submissionFinal = new ExamSubmission();
            $submissionFinal->setStudent($student);
            $submissionFinal->setExam($examFinal);
            $submissionFinal->setGrade($moduleGrades['exam']);
            $submissionFinal->setIsPassed($moduleGrades['exam'] >= 10);
            $manager->persist($submissionFinal);
        }

        $manager->flush();

        // Afficher un résumé
        echo "\n=== DONNÉES DE TEST CRÉÉES ===\n";
        echo "Étudiant: {$student->getPrenom()} {$student->getName()} (ID: {$student->getId()})\n";
        echo "Email: {$studentEmail}\n";
        echo "Téléphone: {$student->getNumtel()}\n";
        echo "Année académique: {$academicYear}\n";
        echo "Semestre: {$semester}\n";
        echo "\nModules avec notes:\n";
        
        foreach ($grades as $module => $gradeData) {
            $ccAvg = array_sum($gradeData['cc']) / count($gradeData['cc']);
            $finalNote = ($ccAvg * 0.10) + ($gradeData['ds'] * 0.20) + ($gradeData['exam'] * 0.70);
            echo "  - {$module}: CC=" . round($ccAvg, 2) . " | DS={$gradeData['ds']} | Exam={$gradeData['exam']} => Note: " . round($finalNote, 2) . "\n";
        }
        echo "\n";
    }
}
