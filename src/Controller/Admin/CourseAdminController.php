<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Form\CourseType;
use App\Service\AiCourseGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/course')]
class CourseAdminController extends AbstractController
{
    #[Route('/new', name: 'admin_course_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        \App\Service\FileUploader $fileUploader
    ): Response {
        $course = new Course();
        $session = $request->getSession();
        if ($session->has('course_draft_title')) {
            $course->setTitle($session->get('course_draft_title', ''));
            $course->setGeneratedContent($session->get('course_draft_content', ''));
            $session->remove('course_draft_title');
            $session->remove('course_draft_content');
        }
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thumbnailFile = $form->get('thumbnail')->getData();
            if ($thumbnailFile) {
                $thumbnailFileName = $fileUploader->upload($thumbnailFile);
                $course->setThumbnailPath($thumbnailFileName);
            }

            $pdfFile = $form->get('pdf')->getData();
            if ($pdfFile) {
                $pdfFileName = $fileUploader->upload($pdfFile);
                $course->setPdfPath($pdfFileName);
            }

            $entityManager->persist($course);
            $entityManager->flush();

            $this->addFlash('success', 'Cours créé avec succès.');
            return $this->redirectToRoute('admin_courses_manage');
        }

        return $this->render('admin/course/form.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_course_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager,
        \App\Service\FileUploader $fileUploader
    ): Response {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thumbnailFile = $form->get('thumbnail')->getData();
            if ($thumbnailFile) {
                $thumbnailFileName = $fileUploader->upload($thumbnailFile);
                $course->setThumbnailPath($thumbnailFileName);
            }

            $pdfFile = $form->get('pdf')->getData();
            if ($pdfFile) {
                $pdfFileName = $fileUploader->upload($pdfFile);
                $course->setPdfPath($pdfFileName);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Cours mis à jour.');
            return $this->redirectToRoute('admin_courses_manage');
        }

        return $this->render('admin/course/form.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
            $this->addFlash('success', 'Cours supprimé.');
        }

        return $this->redirectToRoute('admin_courses_manage');
    }

    #[Route('/{id}/modules', name: 'admin_course_modules', methods: ['GET'])]
    public function manageModules(Course $course): Response
    {
        $modules = $course->getModules();

        return $this->render('admin/course/modules.html.twig', [
            'course' => $course,
            'modules' => $modules,
        ]);
    }

    #[Route('/ai-assistant', name: 'admin_course_ai_assistant', methods: ['GET'])]
    public function aiAssistant(): Response
    {
        return $this->render('admin/course/ai_assistant.html.twig');
    }

    #[Route('/generate-plan', name: 'admin_course_generate_plan', methods: ['POST'])]
    public function generatePlan(Request $request, AiCourseGeneratorService $aiGenerator): JsonResponse
    {
        $data = [];
        if (str_starts_with((string) $request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true) ?? [];
        }
        $title = trim((string) ($data['title'] ?? $request->request->get('title', '')));
        $level = trim((string) ($data['level'] ?? $request->request->get('level', 'Débutant')));
        $duration = trim((string) ($data['duration'] ?? $request->request->get('duration', '1h')));

        if ($title === '') {
            return new JsonResponse(['error' => 'Le titre du cours est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $plan = $aiGenerator->generatePlan($title, $level ?: 'Débutant', $duration ?: '1h');

        return new JsonResponse($plan);
    }

    #[Route('/generate-content', name: 'admin_course_generate_content', methods: ['POST'])]
    public function generateContent(Request $request, AiCourseGeneratorService $aiGenerator): JsonResponse
    {
        $data = [];
        if (str_starts_with((string) $request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true) ?? [];
        }
        $title = trim((string) ($data['title'] ?? $request->request->get('title', '')));
        $level = trim((string) ($data['level'] ?? $request->request->get('level', 'Débutant')));
        $duration = trim((string) ($data['duration'] ?? $request->request->get('duration', '1h')));
        $plan = $data['plan'] ?? $request->request->get('plan') ?? [];

        if ($title === '' || !\is_array($plan)) {
            return new JsonResponse(['error' => 'Titre et plan requis.'], Response::HTTP_BAD_REQUEST);
        }

        $content = $aiGenerator->generateContent($title, $level ?: 'Débutant', $duration ?: '1h', $plan);

        return new JsonResponse(['content' => $content]);
    }

    #[Route('/create-from-ai', name: 'admin_course_create_from_ai', methods: ['POST'])]
    public function createFromAi(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('course_create_from_ai', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_course_ai_assistant');
        }
        $title = trim((string) $request->request->get('title', ''));
        $content = $request->request->get('content', '');
        if ($title !== '') {
            $request->getSession()->set('course_draft_title', $title);
            $request->getSession()->set('course_draft_content', (string) $content);
        }
        return $this->redirectToRoute('admin_course_new');
    }
}
