<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/course')]
class CourseAdminController extends AbstractController
{
    #[Route('/new', name: 'admin_course_new')]
    public function new(): Response
    {
        return $this->render('admin/course/form.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_course_edit')]
    public function edit(int $id): Response
    {
        // Mock course data
        $course = [
            'id' => $id,
            'title' => 'Introduction à l\'Intelligence Artificielle',
            'description' => 'Un cours complet pour comprendre les bases de l\'IA et du Machine Learning.',
            'price' => 49.99
        ];

        return $this->render('admin/course/form.html.twig', [
            'course' => $course
        ]);
    }

    #[Route('/{id}/modules', name: 'admin_course_modules')]
    public function manageModules(int $id): Response
    {
        return $this->render('admin/course/modules.html.twig', ['course_id' => $id]);
    }
}
