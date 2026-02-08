<?php

namespace App\Controller\Student;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
    public function moduleDetails(\App\Entity\Module $module): Response
    {
        return $this->render('student/module/show.html.twig', [
            'module' => $module
        ]);
    }

    #[Route('/course/{id}', name: 'student_course_details')]
    public function courseDetails(\App\Entity\Course $course): Response
    {
        return $this->render('student/course/show.html.twig', [
            'course' => $course
        ]);
    }

    #[Route('/exams', name: 'student_exams_index')]
    public function exams(): Response
    {
        return $this->render('student/exam/index.html.twig');
    }

    #[Route('/exam', name: 'student_exam_show')]
    public function exam(): Response
    {
        return $this->render('student/exam/show.html.twig');
    }
}
