<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/course')]
class CourseAdminController extends AbstractController
{
    #[Route('/new', name: 'admin_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \App\Service\FileUploader $fileUploader): Response
    {
        $course = new Course();
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
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager, \App\Service\FileUploader $fileUploader): Response
    {
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
}
