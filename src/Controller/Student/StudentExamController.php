<?php

namespace App\Controller\Student;

use App\Entity\Exam;
use App\Entity\ExamSubmission;
use App\Repository\ExamRepository;
use App\Repository\ExamSubmissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/student')]
class StudentExamController extends AbstractController
{

    // =========================
    // LIST EXAMS FOR STUDENT
    // =========================
    #[Route('/exams', name: 'student_exams')]
    public function index(
        ExamRepository $examRepository,
        ExamSubmissionRepository $submissionRepository
    ): Response
    {
        // security: must be logged
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $exams = $examRepository->findBy([], ['id' => 'DESC']);

        $student = $this->getUser();

        $submissions = $submissionRepository->findBy([
            'student' => $student
        ]);

        return $this->render('student/exams/index.html.twig', [
            'exams' => $exams,
            'submissions' => $submissions
        ]);
    }



    // =========================
    // PASS EXAM + UPLOAD COPY
    // =========================
    #[Route('/exam/{id}', name: 'student_exam_show')]
    public function show(
        Exam $exam,
        Request $request,
        EntityManagerInterface $em,
        ExamSubmissionRepository $submissionRepository
    ): Response
    {

        // must be logged
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // 🚫 Prevent double submission
        $existingSubmission = $submissionRepository->findOneBy([
            'student' => $this->getUser(),
            'exam' => $exam
        ]);

        if ($existingSubmission) {
            $this->addFlash('warning', 'Vous avez déjà envoyé cet examen.');
            return $this->redirectToRoute('student_exams');
        }


        // FORM SUBMIT
        if ($request->isMethod('POST')) {

            $uploadedFile = $request->files->get('submission_file');

            if ($uploadedFile) {

                // ORIGINAL FILE NAME
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

                // make safe filename
                $safeFilename = preg_replace('/[^A-Za-z0-9\-]/', '_', $originalFilename);

                // IMPORTANT FIX
                $extension = $uploadedFile->guessExtension();

                // fallback if null
                if (!$extension) {
                    $extension = $uploadedFile->getClientOriginalExtension();
                }

                // final name
                $newFilename = uniqid().'_'.$safeFilename.'.'.$extension;

                // ensure directory exists
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/submissions';

                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                try {
                    $uploadedFile->move($uploadDir, $newFilename);
                } catch (FileException $e) {
                    dd($e->getMessage());
                }

                // SAVE IN DATABASE
                $submission = new ExamSubmission();
                $submission->setStudent($this->getUser());
                $submission->setExam($exam);
                $submission->setFilePath($newFilename);
                $submission->setSubmittedAt(new \DateTime()); // IMPORTANT (not DateTimeImmutable)
                $submission->setGrade(null);
                $submission->setIsPassed(null);

                $em->persist($submission);
                $em->flush();

                $this->addFlash('success', 'Votre copie a été envoyée au professeur !');

                return $this->redirectToRoute('student_exams');
            }

            $this->addFlash('danger', 'Veuillez uploader un fichier.');
        }

        return $this->render('student/exams/show.html.twig', [
            'exam' => $exam
        ]);
    }
    #[Route('/whoami', name: 'whoami')]
public function whoami(): Response
{
    dd($this->getUser(), $this->getUser()->getRoles());
}

}
