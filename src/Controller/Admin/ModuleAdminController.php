<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Form\ModuleType;
use App\Repository\CourseRepository;
use App\Repository\ModuleRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/modules')]
class ModuleAdminController extends AbstractController
{
    #[Route('/', name: 'admin_modules_manage', methods: ['GET'])]
    public function index(ModuleRepository $moduleRepository): Response
    {
        $modules = $moduleRepository->findBy([], ['id' => 'ASC']);

        return $this->render('admin/module/index.html.twig', [
            'modules' => $modules,
        ]);
    }

    #[Route('/new', name: 'admin_module_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository,
        FileUploader $fileUploader
    ): Response {
        $courseId = $request->query->getInt('course_id');
        $course = null;
        $options = [];

        if ($courseId > 0) {
            $course = $courseRepository->find($courseId);
            if ($course) {
                $options['include_course'] = false;
            }
        } else {
            $options['include_course'] = true;
        }

        $module = new Module();
        $form = $this->createForm(ModuleType::class, $module, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thumbnailFile = $form->get('thumbnail')->getData();
            if ($thumbnailFile) {
                $thumbnailFileName = $fileUploader->upload($thumbnailFile);
                $module->setThumbnail($thumbnailFileName);
            }

            $entityManager->persist($module);

            if ($course) {
                $course->addModule($module);
            } else {
                // Handle courses selected in the form (inverse side)
                foreach ($module->getCourses() as $selectedCourse) {
                    $selectedCourse->addModule($module);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Module créé avec succès.');

            if ($course) {
                return $this->redirectToRoute('admin_course_modules', ['id' => $course->getId()]);
            }

            return $this->redirectToRoute('admin_modules_manage');
        }

        return $this->render('admin/module/form.html.twig', [
            'module' => $module,
            'form' => $form,
            'course' => $course,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_module_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Module $module,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader
    ): Response {
        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thumbnailFile = $form->get('thumbnail')->getData();
            if ($thumbnailFile) {
                $thumbnailFileName = $fileUploader->upload($thumbnailFile);
                $module->setThumbnail($thumbnailFileName);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Module mis à jour.');
            return $this->redirectToRoute('admin_modules_manage');
        }

        return $this->render('admin/module/form.html.twig', [
            'module' => $module,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_module_delete', methods: ['POST'])]
    public function delete(Request $request, Module $module, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $module->getId(), $request->request->get('_token'))) {
            $entityManager->remove($module);
            $entityManager->flush();
            $this->addFlash('success', 'Module supprimé.');
        }

        return $this->redirectToRoute('admin_modules_manage');
    }
}
