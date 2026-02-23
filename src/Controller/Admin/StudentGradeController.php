<?php

namespace App\Controller\Admin;

use App\Entity\StudentModuleGrade;
use App\Entity\User;
use App\Repository\StudentModuleGradeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/grades')]
class StudentGradeController extends AbstractController
{
    #[Route('/', name: 'admin_student_grade_index')]
    public function index(Request $request, StudentModuleGradeRepository $gradeRepo, EntityManagerInterface $em): Response
    {
        $academicYear = $request->query->get('academicYear', date('Y') . '/' . (date('Y') + 1));
        $semester = $request->query->get('semester', '');
        $studentId = $request->query->get('student', '');

        $students = $em->getRepository(User::class)->findBy(['role' => ['Étudiant', 'ROLE_ETUDIANT', 'etudiant']], ['name' => 'ASC']);
        
        $grades = [];
        if ($studentId && $academicYear && $semester) {
            $grades = $gradeRepo->findByStudentAndPeriod($studentId, $academicYear, $semester);
        }

        return $this->render('admin/student_grade/index.html.twig', [
            'students' => $students,
            'grades' => $grades,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'studentId' => $studentId,
        ]);
    }

    #[Route('/new', name: 'admin_student_grade_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $students = $em->getRepository(User::class)->findBy(['role' => ['Étudiant', 'ROLE_ETUDIANT', 'etudiant']], ['name' => 'ASC']);
        
        if ($request->isMethod('POST')) {
            $studentId = $request->request->get('student');
            $academicYear = $request->request->get('academicYear');
            $semester = $request->request->get('semester');
            $moduleName = $request->request->get('moduleName');
            $coefficient = $request->request->get('coefficient', 1);
            $noteCC = $request->request->get('noteCC');
            $noteDS = $request->request->get('noteDS');
            $noteExam = $request->request->get('noteExam');

            $student = $em->getRepository(User::class)->find($studentId);
            if (!$student) {
                $this->addFlash('error', 'Étudiant non trouvé.');
                return $this->redirectToRoute('admin_student_grade_new');
            }

            $grade = new StudentModuleGrade();
            $grade->setStudent($student);
            $grade->setAcademicYear($academicYear);
            $grade->setSemester($semester);
            $grade->setModuleName($moduleName);
            $grade->setCoefficient((float) $coefficient);
            $grade->setNoteCC($noteCC ? (float) $noteCC : null);
            $grade->setNoteDS($noteDS ? (float) $noteDS : null);
            $grade->setNoteExam($noteExam ? (float) $noteExam : null);

            $em->persist($grade);
            $em->flush();

            $this->addFlash('success', 'Note ajoutée avec succès.');
            return $this->redirectToRoute('admin_student_grade_index', [
                'student' => $studentId,
                'academicYear' => $academicYear,
                'semester' => $semester,
            ]);
        }

        return $this->render('admin/student_grade/new.html.twig', [
            'students' => $students,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_student_grade_edit', methods: ['GET', 'POST'])]
    public function edit(StudentModuleGrade $grade, Request $request, EntityManagerInterface $em): Response
    {
        $students = $em->getRepository(User::class)->findBy(['role' => ['Étudiant', 'ROLE_ETUDIANT', 'etudiant']], ['name' => 'ASC']);
        
        if ($request->isMethod('POST')) {
            $grade->setModuleName($request->request->get('moduleName'));
            $grade->setCoefficient((float) $request->request->get('coefficient', 1));
            $grade->setNoteCC($request->request->get('noteCC') ? (float) $request->request->get('noteCC') : null);
            $grade->setNoteDS($request->request->get('noteDS') ? (float) $request->request->get('noteDS') : null);
            $grade->setNoteExam($request->request->get('noteExam') ? (float) $request->request->get('noteExam') : null);

            $em->flush();

            $this->addFlash('success', 'Note mise à jour.');
            return $this->redirectToRoute('admin_student_grade_index', [
                'student' => $grade->getStudent()->getId(),
                'academicYear' => $grade->getAcademicYear(),
                'semester' => $grade->getSemester(),
            ]);
        }

        return $this->render('admin/student_grade/edit.html.twig', [
            'grade' => $grade,
            'students' => $students,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_student_grade_delete', methods: ['POST'])]
    public function delete(StudentModuleGrade $grade, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $grade->getId(), $request->request->get('_token'))) {
            $studentId = $grade->getStudent()->getId();
            $year = $grade->getAcademicYear();
            $semester = $grade->getSemester();
            
            $em->remove($grade);
            $em->flush();

            $this->addFlash('success', 'Note supprimée.');
            return $this->redirectToRoute('admin_student_grade_index', [
                'student' => $studentId,
                'academicYear' => $year,
                'semester' => $semester,
            ]);
        }

        return $this->redirectToRoute('admin_student_grade_index');
    }

    #[Route('/bulk-add', name: 'admin_student_grade_bulk', methods: ['GET', 'POST'])]
    public function bulkAdd(Request $request, EntityManagerInterface $em): Response
    {
        $students = $em->getRepository(User::class)->findBy(['role' => ['Étudiant', 'ROLE_ETUDIANT', 'etudiant']], ['name' => 'ASC']);
        
        if ($request->isMethod('POST')) {
            $studentId = $request->request->get('student');
            $academicYear = $request->request->get('academicYear');
            $semester = $request->request->get('semester');
            $modules = $request->request->all('modules');

            $student = $em->getRepository(User::class)->find($studentId);
            if (!$student) {
                $this->addFlash('error', 'Étudiant non trouvé.');
                return $this->redirectToRoute('admin_student_grade_bulk');
            }

            $count = 0;
            foreach ($modules as $moduleData) {
                if (empty($moduleData['moduleName'])) continue;

                $grade = new StudentModuleGrade();
                $grade->setStudent($student);
                $grade->setAcademicYear($academicYear);
                $grade->setSemester($semester);
                $grade->setModuleName($moduleData['moduleName']);
                $grade->setCoefficient((float) ($moduleData['coefficient'] ?? 1));
                $grade->setNoteCC(isset($moduleData['noteCC']) && $moduleData['noteCC'] !== '' ? (float) $moduleData['noteCC'] : null);
                $grade->setNoteDS(isset($moduleData['noteDS']) && $moduleData['noteDS'] !== '' ? (float) $moduleData['noteDS'] : null);
                $grade->setNoteExam(isset($moduleData['noteExam']) && $moduleData['noteExam'] !== '' ? (float) $moduleData['noteExam'] : null);

                $em->persist($grade);
                $count++;
            }

            $em->flush();

            $this->addFlash('success', "$count note(s) ajoutée(s) avec succès.");
            return $this->redirectToRoute('admin_student_grade_index', [
                'student' => $studentId,
                'academicYear' => $academicYear,
                'semester' => $semester,
            ]);
        }

        return $this->render('admin/student_grade/bulk.html.twig', [
            'students' => $students,
        ]);
    }
}
