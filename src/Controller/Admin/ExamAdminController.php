<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Exam;
use App\Form\ExamType;
use App\Repository\ExamRepository;
use App\Service\ExamSimilarityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/admin/exam')]
class ExamAdminController extends AbstractController
{
    #[Route('/manage', name: 'admin_exams_manage')]
    public function manage(ExamRepository $examRepository): Response
    {
        return $this->render('admin/exam/manage.html.twig', [
            'exams' => $examRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_exam_new')]
    #[Route('/{id}/edit', name: 'admin_exam_edit')]
    public function examForm(Request $request, EntityManagerInterface $entityManager, Exam $exam = null): Response
    {
        if (!$exam) {
            $exam = new Exam();
        }

        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('filePath')->getData();
            if ($file) {
                $projectDir = $this->getParameter('kernel.project_dir');
                $uploadDir = $projectDir . '/public/uploads/exams';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '-', $file->getClientOriginalName());
                $newFilename = uniqid('', true) . '-' . $safeName;
                try {
                    $file->move($uploadDir, $newFilename);
                    $exam->setFilePath('uploads/exams/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Impossible d\'enregistrer le fichier PDF.');
                }
            }
            $entityManager->persist($exam);
            $entityManager->flush();

            $this->addFlash('success', 'Examen enregistré avec succès !');

            return $this->redirectToRoute('admin_exams_manage');
        }

        return $this->render('admin/exam/form.html.twig', [
            'form' => $form->createView(),
            'exam' => $exam,
            'isEdit' => $exam->getId() !== null
        ]);
    }

    #[Route('/{id}/questions', name: 'admin_exam_questions')]
    public function manageQuestions(Exam $exam): Response
    {
        return $this->render('admin/exam/questions.html.twig', [
            'exam' => $exam
        ]);
    }

    #[Route('/{id}/similarity', name: 'admin_exam_similarity')]
    public function similarity(Exam $exam, ExamSimilarityService $similarityService): Response
    {
        $report = $similarityService->getSimilarityReport($exam);

        return $this->render('admin/exam/similarity.html.twig', [
            'exam' => $exam,
            'pairs' => $report['pairs'],
            'error' => $report['error'],
            'suspicious_threshold' => ExamSimilarityService::getSuspiciousThreshold(),
            'high_threshold' => ExamSimilarityService::getHighThreshold(),
        ]);
    }

    #[Route('/{id}/dashboard', name: 'admin_exam_dashboard')]
    public function dashboard(Exam $exam): Response
    {
        $submissions = $exam->getSubmissions()->toArray();
        usort($submissions, fn($a, $b) => ($b->getStartedAt() ?? $b->getSubmittedAt()) <=> ($a->getStartedAt() ?? $a->getSubmittedAt()));

        $stats = [
            'total' => \count($submissions),
            'in_progress' => 0,
            'submitted' => 0,
            'closed_no_submit' => 0,
            'grades_sum' => 0.0,
            'grades_count' => 0,
            'passed' => 0,
        ];
        foreach ($submissions as $s) {
            if ($s->getFilePath() !== null) {
                $stats['submitted']++;
                if ($s->getGrade() !== null) {
                    $stats['grades_sum'] += $s->getGrade();
                    $stats['grades_count']++;
                    if ($s->isPassed()) {
                        $stats['passed']++;
                    }
                }
            } elseif ($s->isClosed()) {
                $stats['closed_no_submit']++;
            } else {
                $stats['in_progress']++;
            }
        }
        $stats['average'] = $stats['grades_count'] > 0 ? round($stats['grades_sum'] / $stats['grades_count'], 2) : null;
        $stats['pass_rate'] = $stats['grades_count'] > 0 ? round(100 * $stats['passed'] / $stats['grades_count'], 1) : null;

        return $this->render('admin/exam/dashboard.html.twig', [
            'exam' => $exam,
            'submissions' => $submissions,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/corrections', name: 'admin_exam_corrections')]
    public function corrections(Exam $exam): Response
    {
        $submissions = $exam->getSubmissions()->toArray();
        usort($submissions, fn($a, $b) => ($b->getStartedAt() ?? $b->getSubmittedAt()) <=> ($a->getStartedAt() ?? $a->getSubmittedAt()));
        $submissionsWithFile = array_values(array_filter($submissions, fn($s) => $s->getFilePath() !== null));

        return $this->render('admin/exam/corrections.html.twig', [
            'exam' => $exam,
            'submissions' => $submissionsWithFile,
        ]);
    }

    #[Route('/{id}/grades', name: 'admin_exam_save_grades', methods: ['POST'])]
    public function saveGrades(Exam $exam, Request $request, EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(\App\Entity\ExamSubmission::class);
        $grades = $request->request->all('grades') ?? [];
        $passed = $request->request->all('passed') ?? [];
        $updated = 0;
        foreach ($grades as $submissionId => $gradeStr) {
            $submission = $repo->find($submissionId);
            if (!$submission || $submission->getExam() !== $exam) {
                continue;
            }
            $grade = null;
            if ($gradeStr !== '' && $gradeStr !== null && is_numeric($gradeStr)) {
                $grade = (float) $gradeStr;
                $grade = max(0, min(20, $grade));
            }
            $submission->setGrade($grade);
            $submission->setIsPassed(isset($passed[$submissionId]) && (bool) $passed[$submissionId]);
            $em->persist($submission);
            $updated++;
        }
        $em->flush();
        $this->addFlash('success', $updated > 0 ? $updated . ' note(s) enregistrée(s).' : 'Aucune modification.');
        return $this->redirectToRoute('admin_exam_corrections', ['id' => $exam->getId()]);
    }

    #[Route('/{id}/export', name: 'admin_exam_export_csv', methods: ['GET'])]
    public function exportCsv(Exam $exam): Response
    {
        $submissions = $exam->getSubmissions()->toArray();
        usort($submissions, fn($a, $b) => ($b->getStartedAt() ?? $b->getSubmittedAt()) <=> ($a->getStartedAt() ?? $a->getSubmittedAt()));

        $response = new StreamedResponse(function () use ($exam, $submissions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Candidat', 'Email', 'Début', 'Fin / Clôture', 'Statut', 'Note', 'Validé'], ';');
            foreach ($submissions as $s) {
                $candidate = $s->getStudent() ? ($s->getStudent()->getName() ?? $s->getStudent()->getEmail()) : ($s->getCandidateIdentifier() ?? 'Anonyme');
                $email = $s->getStudent() ? $s->getStudent()->getEmail() : '—';
                $started = ($s->getStartedAt() ?? $s->getSubmittedAt())?->format('d/m/Y H:i') ?? '—';
                $ended = $s->getClosedAt() ? $s->getClosedAt()->format('d/m/Y H:i') : ($s->getFilePath() ? $s->getSubmittedAt()->format('d/m/Y H:i') : '—');
                $status = $s->getFilePath() ? 'Rendu' : ($s->isClosed() ? 'Clôturé (abandon/temps)' : 'En cours');
                $grade = $s->getGrade() !== null ? (string) $s->getGrade() : '—';
                $passed = $s->isPassed() === true ? 'Oui' : ($s->isPassed() === false ? 'Non' : '—');
                fputcsv($out, [$candidate, $email, $started, $ended, $status, $grade, $passed], ';');
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="exam-' . $exam->getId() . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $exam->getTitle()) . '.csv"');
        return $response;
    }

    #[Route('/{id}/delete', name: 'admin_exam_delete', methods: ['POST'])]
    public function delete(Request $request, Exam $exam, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exam->getId(), $request->request->get('_token'))) {
            $entityManager->remove($exam);
            $entityManager->flush();
            $this->addFlash('success', 'Examen supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_exams_manage');
    }
}
