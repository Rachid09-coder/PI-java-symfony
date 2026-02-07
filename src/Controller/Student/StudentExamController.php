<?php

namespace App\Controller\Student;

use App\Entity\Exam;
use App\Entity\ExamSubmission;
use App\Repository\ExamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Repository\ExamSubmissionRepository;

class StudentExamController extends AbstractController
{
    #[Route('/student/exams', name: 'student_exams')]
    public function index(ExamRepository $examRepo): Response
    {
        $exams = $examRepo->findAll();

        return $this->render('student/exams/index.html.twig', [
            'exams' => $exams
        ]);
    }

    #[Route('/student/exam/{id}', name: 'student_exam_show')]
    public function show(Exam $exam, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {

            $submission = new ExamSubmission();
            $submission->setStudent($this->getUser());
            $submission->setExam($exam);
            $submission->setSubmittedAt(new \DateTime());

            $em->persist($submission);
            $em->flush();

            $this->addFlash('success', 'Examen envoyé avec succès !');
            return $this->redirectToRoute('student_exams');
        }

        return $this->render('student/exams/show.html.twig', [
            'exam' => $exam
        ]);
    }
}
