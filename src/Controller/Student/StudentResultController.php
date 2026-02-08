<?php

namespace App\Controller\Student;

use App\Repository\ExamSubmissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STUDENT')]
#[Route('/student')]
class StudentResultController extends AbstractController
{

    #[Route('/results', name: 'student_results')]
    public function results(ExamSubmissionRepository $submissionRepository): Response
    {
        $student = $this->getUser();

        // only this student's copies
        $submissions = $submissionRepository->findBy([
            'student' => $student
        ]);

        return $this->render('student/results/index.html.twig', [
            'submissions' => $submissions
        ]);
    }
}
