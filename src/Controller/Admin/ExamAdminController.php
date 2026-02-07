<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/exam')]
class ExamAdminController extends AbstractController
{
    #[Route('/manage', name: 'admin_exams_manage')]
    public function manage(): Response
    {
        return $this->render('admin/exam/manage.html.twig');
    }

    #[Route('/new', name: 'admin_exam_new')]
    #[Route('/{id}/edit', name: 'admin_exam_edit')]
    public function examForm(?int $id = null): Response
    {
        return $this->render('admin/exam/form.html.twig', ['id' => $id]);
    }

    #[Route('/{id}/questions', name: 'admin_exam_questions')]
    public function manageQuestions(int $id): Response
    {
        return $this->render('admin/exam/questions.html.twig', ['id' => $id]);
    }
}
