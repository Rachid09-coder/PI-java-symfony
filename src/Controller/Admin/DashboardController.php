<?php

namespace App\Controller\Admin;

use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'total_students' => 1250,
                'total_courses' => 45,
                'total_exams' => 12,
                'revenue' => '15,400 €'
            ]
        ]);
    }

    #[Route('/courses/manage', name: 'admin_courses_manage')]
    public function manageCourses(CourseRepository $courseRepository): Response
    {
        return $this->render('admin/course/manage.html.twig', [
            'courses' => $courseRepository->findAllOrderedByTitle(),
        ]);
    }

    #[Route('/exams/manage', name: 'admin_exams_manage')]
    public function manageExams(): Response
    {
        return $this->render('admin/exam/manage.html.twig');
    }
}
