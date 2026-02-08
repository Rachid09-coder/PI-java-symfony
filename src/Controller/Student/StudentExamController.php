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

    // only students allowed
    if (!$this->isGranted('ROLE_STUDENT')) {
        throw $this->createAccessDeniedException('Seuls les étudiants peuvent envoyer une copie.');
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

        // ❌ no file
        if (!$uploadedFile) {
            $this->addFlash('danger', 'Veuillez sélectionner un fichier.');
            return $this->redirectToRoute('student_exam_show', ['id' => $exam->getId()]);
        }

        // ❌ empty file
        if ($uploadedFile->getSize() === 0) {
            $this->addFlash('danger', 'Le fichier est vide.');
            return $this->redirectToRoute('student_exam_show', ['id' => $exam->getId()]);
        }

        // ❌ file too big (5MB)
        if ($uploadedFile->getSize() > 5 * 1024 * 1024) {
            $this->addFlash('danger', 'Le fichier dépasse la taille autorisée (5MB max).');
            return $this->redirectToRoute('student_exam_show', ['id' => $exam->getId()]);
        }

        // allowed extensions
        $allowedExtensions = ['pdf', 'doc', 'docx'];

        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            $this->addFlash('danger', 'Type de fichier interdit. Formats autorisés : PDF, DOC, DOCX.');
            return $this->redirectToRoute('student_exam_show', ['id' => $exam->getId()]);
        }

        // block php files
        if ($extension === 'php' || str_contains($uploadedFile->getClientOriginalName(), '.php')) {
            $this->addFlash('danger', 'Fichier dangereux détecté.');
            return $this->redirectToRoute('student_exam_show', ['id' => $exam->getId()]);
        }

        // SAFE FILENAME
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^A-Za-z0-9\-]/', '_', $originalFilename);

        $newFilename = uniqid('exam_', true).'_'.$safeFilename.'.'.$extension;

        // upload directory
        $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/submissions';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        try {
            $uploadedFile->move($uploadDir, $newFilename);
        } catch (FileException $e) {
            $this->addFlash('danger', 'Erreur lors de l’upload du fichier.');
            return $this->redirectToRoute('student_exam_show', ['id' => $exam->getId()]);
        }

        // SAVE DATABASE
        $submission = new ExamSubmission();
        $submission->setStudent($this->getUser());
        $submission->setExam($exam);
        $submission->setFilePath($newFilename);
        $submission->setSubmittedAt(new \DateTime());
        $submission->setGrade(null);
        $submission->setIsPassed(null);

        $em->persist($submission);
        $em->flush();

        $this->addFlash('success', 'Votre copie a été envoyée au professeur !');
        return $this->redirectToRoute('student_exams');
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
