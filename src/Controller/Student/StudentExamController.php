<?php

namespace App\Controller\Student;

use App\Entity\Exam;
use App\Entity\ExamSubmission;
use App\Entity\User;
use App\Repository\ExamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/student/exams')]
class StudentExamController extends AbstractController
{
    #[Route('/', name: 'student_exams_index')]
    public function index(ExamRepository $examRepository): Response
    {
        return $this->render('student/exam/index.html.twig', [
            'exams' => $examRepository->findAll(),
        ]);
    }

    private function getCandidateIdentifier(Request $request): ?string
    {
        $user = $this->getUser();
        if ($user) {
            return 'user_' . $user->getId();
        }
        
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        
        return $session->get('exam_candidate_id');
    }

    #[Route('/{id}', name: 'student_exam_show')]
    public function show(Exam $exam, EntityManagerInterface $em, Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $candidateId = $this->getCandidateIdentifier($request);
        
        $criteria = ['exam' => $exam];
        if ($user) {
            $criteria['student'] = $user;
        } elseif ($candidateId) {
            $criteria['candidateIdentifier'] = $candidateId;
        } else {
            // No user and no candidate session yet
            $submission = null;
            return $this->render('student/exam/show.html.twig', [
                'exam' => $exam,
                'submission' => null
            ]);
        }
        
        // Find existing submission
        $submission = $em->getRepository(ExamSubmission::class)->findOneBy($criteria);

        return $this->render('student/exam/show.html.twig', [
            'exam' => $exam,
            'submission' => $submission
        ]);
    }

    #[Route('/{id}/start', name: 'student_exam_start', methods: ['POST'])]
    public function start(Exam $exam, EntityManagerInterface $em, Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        
        $candidateId = $this->getCandidateIdentifier($request);

        if (!$user && !$candidateId) {
            // Generate a new anonymous candidate ID
            $candidateId = uniqid('anon_', true);
            $session->set('exam_candidate_id', $candidateId);
        }

        $criteria = ['exam' => $exam];
        if ($user) {
            $criteria['student'] = $user;
        } else {
            $criteria['candidateIdentifier'] = $candidateId;
        }

        // Check if already started
        $existing = $em->getRepository(ExamSubmission::class)->findOneBy($criteria);

        if (!$existing) {
            $submission = new ExamSubmission();
            $submission->setExam($exam);
            if ($user) {
                $submission->setStudent($user);
            } else {
                $submission->setCandidateIdentifier($candidateId);
            }
            $submission->setSubmittedAt(new \DateTimeImmutable());
            
            $em->persist($submission);
            $em->flush();
        }

        return $this->redirectToRoute('student_exam_show', ['id' => $exam->getId()]);
    }

    #[Route('/{id}/submit', name: 'student_exam_submit', methods: ['POST'])]
    public function submit(Request $request, Exam $exam, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $candidateId = $this->getCandidateIdentifier($request);

        $criteria = ['exam' => $exam];
        if ($user) {
            $criteria['student'] = $user;
        } elseif ($candidateId) {
            $criteria['candidateIdentifier'] = $candidateId;
        } else {
             return $this->redirectToRoute('student_exams_index');
        }

        $submission = $em->getRepository(ExamSubmission::class)->findOneBy($criteria);

        if (!$submission) {
            return $this->redirectToRoute('student_exams_index');
        }

        if ($exam->getType() === 'QCM') {
            $score = 0;
            $totalPoints = 0;
            $answers = $request->request->all('answers');

            foreach ($exam->getQuestions() as $question) {
                $totalPoints += $question->getPoints();
                $selectedChoiceId = $answers[$question->getId()] ?? null;
                
                if ($selectedChoiceId) {
                    foreach ($question->getChoices() as $choice) {
                        if ($choice->getId() == $selectedChoiceId && $choice->isIsCorrect()) {
                            $score += $question->getPoints();
                            break;
                        }
                    }
                }
            }

            $submission->setGrade($score);
            // Pass if > 50% (simple logic)
            $submission->setIsPassed($totalPoints > 0 && ($score / $totalPoints) >= 0.5);

        } elseif (in_array($exam->getType(), ['pdf', 'Devoir', 'Projet'])) {
            /** @var UploadedFile $file */
            $file = $request->files->get('submissionFile');
            
            if ($file) {
                 $newFilename = uniqid().'.'.$file->guessExtension();
                 try {
                     $file->move(
                         $this->getParameter('kernel.project_dir') . '/public/uploads/submissions',
                         $newFilename
                     );
                     $submission->setFilePath('uploads/submissions/'.$newFilename);
                     $submission->setGrade(null); // Pending grading
                     $submission->setIsPassed(null);
                 } catch (FileException $e) {
                     $this->addFlash('error', 'Erreur lors de l\'envoi du fichier.');
                     return $this->redirectToRoute('student_exam_show', ['id' => $exam->getId()]);
                 }
            }
        }

        $submission->setSubmittedAt(new \DateTimeImmutable());
        
        $em->flush();

        return $this->redirectToRoute('student_exam_show', ['id' => $exam->getId()]);
    }
}
