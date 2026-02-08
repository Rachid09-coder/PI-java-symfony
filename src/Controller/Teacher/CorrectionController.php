<?php

namespace App\Controller\Teacher;

use App\Entity\ExamSubmission;
use App\Repository\ExamSubmissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TEACHER')]

#[Route('/teacher')]
class CorrectionController extends AbstractController
{

    // =========================
    // LIST ALL SUBMISSIONS
    // =========================
    #[Route('/corrections', name: 'teacher_corrections')]
    public function index(ExamSubmissionRepository $submissionRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TEACHER');

        $submissions = $submissionRepository->findBy([], ['submittedAt' => 'DESC']);

        return $this->render('teacher/corrections/index.html.twig', [
            'submissions' => $submissions
        ]);
    }



    // =========================
    // GRADE A SUBMISSION
    // =========================
    #[Route('/corrections/{id}', name: 'teacher_grade')]
    public function grade(
        ExamSubmission $submission,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_TEACHER');

        if ($request->isMethod('POST')) {

            $grade = $request->request->get('grade');
            $passed = $request->request->get('isPassed');

            $submission->setGrade($grade);
            $submission->setIsPassed($passed === '1');

            $em->flush();

            $this->addFlash('success', 'Correction enregistrée ✔');

            return $this->redirectToRoute('teacher_corrections');
        }

        return $this->render('teacher/corrections/grade.html.twig', [
            'submission' => $submission
        ]);
    }
}
