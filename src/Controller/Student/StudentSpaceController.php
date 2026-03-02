<?php

namespace App\Controller\Student;

use App\Entity\Course;
use App\Entity\CourseProgress;
use App\Repository\CourseProgressRepository;
use App\Repository\CourseRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/student')]
class StudentSpaceController extends AbstractController
{
    #[Route('/courses', name: 'student_courses')]
    public function courses(\App\Repository\ModuleRepository $moduleRepository): Response
    {
        $modules = $moduleRepository->findBy([], ['id' => 'ASC']);

        return $this->render('student/module/index.html.twig', [
            'modules' => $modules
        ]);
    }

    #[Route('/modules/{id}', name: 'student_module_details')]
    public function moduleDetails(\App\Entity\Module $module, CourseProgressRepository $progressRepo): Response
    {
        $user = $this->getUser();
        $courseProgressMap = [];
        if ($user) {
            foreach ($module->getCourses() as $course) {
                $progress = $progressRepo->findOneByStudentAndCourse($user, $course);
                $courseProgressMap[$course->getId()] = $progress ? $progress->getProgressPercent() : 0;
            }
        }

        return $this->render('student/module/show.html.twig', [
            'module' => $module,
            'courseProgressMap' => $courseProgressMap,
        ]);
    }

    #[Route('/course/{id}', name: 'student_course_details')]
    public function courseDetails(Course $course, CourseProgressRepository $progressRepo): Response
    {
        $user = $this->getUser();
        $progress = null;
        if ($user) {
            $progress = $progressRepo->findOneByStudentAndCourse($user, $course);
            if (!$progress) {
                $progress = new CourseProgress();
                $progress->setStudent($user);
                $progress->setCourse($course);
                $progressRepo->save($progress);
            } else {
                $progress->setLastAccessedAt(new \DateTimeImmutable());
                $progressRepo->save($progress);
            }
        }

        return $this->render('student/course/show.html.twig', [
            'course' => $course,
            'progress' => $progress,
        ]);
    }

    #[Route('/course/{id}/progress', name: 'student_course_progress', methods: ['POST'])]
    public function updateCourseProgress(Course $course, Request $request, CourseProgressRepository $progressRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $progress = $progressRepo->findOneByStudentAndCourse($user, $course);
        if (!$progress) {
            $progress = new CourseProgress();
            $progress->setStudent($user);
            $progress->setCourse($course);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $step = isset($data['step']) ? (int) $data['step'] : null;
        $percent = isset($data['percent']) ? (int) $data['percent'] : null;

        if ($step !== null) {
            $percent = match ($step) {
                1 => 33,
                2 => 66,
                3 => 100,
                default => $progress->getProgressPercent(),
            };
        }
        if ($percent !== null) {
            $progress->setProgressPercent($percent);
        } else {
            $progress->setProgressPercent(100);
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        $progress->setLastAccessedAt(new \DateTimeImmutable());
        $progressRepo->save($progress);

        return new JsonResponse([
            'success' => true,
            'progressPercent' => $progress->getProgressPercent(),
            'completedAt' => $progress->getCompletedAt()?->format('c'),
        ]);
    }

    #[Route('/course/{id}/export-pdf', name: 'student_course_export_pdf', methods: ['GET'])]
    public function exportCoursePdf(Course $course): Response
    {
        $user = $this->getUser();
        $studentName = $user
            ? trim($user->getPrenom() . ' ' . $user->getName())
            : 'Étudiant';
        $exportDate = (new \DateTimeImmutable())->format('d/m/Y à H:i');

        $html = $this->renderView('student/course/export_pdf.html.twig', [
            'course' => $course,
            'studentName' => $studentName,
            'exportDate' => $exportDate,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();
        $projectDir = $this->getParameter('kernel.project_dir');
        $coursePdfPath = $course->getPdfPath();
        $coursePdfFullPath = $coursePdfPath ? $projectDir . '/public/uploads/' . $coursePdfPath : null;

        if ($coursePdfFullPath && is_file($coursePdfFullPath)) {
            $tempOurPdf = tempnam(sys_get_temp_dir(), 'edusmart_') . '.pdf';
            file_put_contents($tempOurPdf, $pdfOutput);

            try {
                $fpdi = new \setasign\Fpdi\Fpdi();
                $pageCount = $fpdi->setSourceFile($tempOurPdf);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplId = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($tplId);
                    $fpdi->AddPage($size['orientation'], $size);
                    $fpdi->useTemplate($tplId);
                }
                $coursePageCount = $fpdi->setSourceFile($coursePdfFullPath);
                for ($i = 1; $i <= $coursePageCount; $i++) {
                    $tplId = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($tplId);
                    $fpdi->AddPage($size['orientation'], $size);
                    $fpdi->useTemplate($tplId);
                }
                $pdfOutput = $fpdi->output('S');
            } catch (\Throwable $e) {
                // FPDI not installed or merge failed: keep Dompdf output only (cover + summary)
            } finally {
                @unlink($tempOurPdf);
            }
        }

        $filename = sprintf(
            'Mon-cours-%s-%s.pdf',
            preg_replace('/[^a-zA-Z0-9\-]/', '-', $course->getTitle()),
            (new \DateTimeImmutable())->format('Y-m-d')
        );

        $response = new Response($pdfOutput);
        $response->headers->set('Content-Type', 'application/pdf');
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/exams', name: 'student_exams_index')]
    public function exams(\App\Repository\ExamRepository $examRepository): Response
    {
        $exams = $examRepository->findBy([], ['id' => 'DESC']);
        
        return $this->render('student/exam/index.html.twig', [
            'exams' => $exams
        ]);
    }

    #[Route('/calendar', name: 'student_calendar')]
    public function calendar(CourseRepository $courseRepository): Response
    {
        $courses = $courseRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('student/calendar/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    #[Route('/notifications', name: 'student_notifications', methods: ['GET'])]
    public function notifications(CourseRepository $courseRepository): JsonResponse
    {
        $courses = $courseRepository->findBy([], ['createdAt' => 'DESC']);
        $notifications = [];
        $now = new \DateTimeImmutable();

        foreach ($courses as $course) {
            $createdAt = $course->getCreatedAt();
            $daysSince = (int) $now->diff($createdAt)->days;

            if ($daysSince > 30) {
                continue;
            }

            $thumbnailUrl = $course->getThumbnailPath()
                ? '/uploads/' . $course->getThumbnailPath()
                : null;

            $notifications[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription() ?? '',
                'price' => $course->getPrice(),
                'status' => $course->getStatus(),
                'thumbnail' => $thumbnailUrl,
                'url' => $this->generateUrl('student_course_details', ['id' => $course->getId()]),
                'createdAt' => $createdAt->format('c'),
                'daysSince' => $daysSince,
                'modules' => $course->getModules()->count(),
            ];
        }

        return new JsonResponse($notifications);
    }

    #[Route('/calendar/events', name: 'student_calendar_events', methods: ['GET'])]
    public function calendarEvents(CourseRepository $courseRepository): JsonResponse
    {
        $courses = $courseRepository->findAll();
        $events = [];

        $colors = ['#667eea', '#f5576c', '#4facfe', '#43e97b', '#fa709a', '#a18cd1'];

        foreach ($courses as $i => $course) {
            $color = $colors[$i % count($colors)];
            $events[] = [
                'id' => 'course-' . $course->getId(),
                'title' => $course->getTitle(),
                'start' => $course->getCreatedAt()->format('Y-m-d'),
                'url' => $this->generateUrl('student_course_details', ['id' => $course->getId()]),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'description' => $course->getDescription() ?? '',
                    'price' => $course->getPrice(),
                    'status' => $course->getStatus(),
                    'modules' => $course->getModules()->count(),
                    'hasPdf' => $course->getPdfPath() !== null,
                    'type' => 'course',
                ],
            ];
        }

        return new JsonResponse($events);
    }
}
