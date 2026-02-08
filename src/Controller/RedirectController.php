<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RedirectController extends AbstractController
{
    #[Route('/redirect-after-login', name: 'app_redirect_after_login')]
    public function redirectAfterLogin(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_exam_manage');
        }

        if ($this->isGranted('ROLE_TEACHER')) {
            return $this->redirectToRoute('admin_exam_manage');
        }

        if ($this->isGranted('ROLE_STUDENT')) {
            return $this->redirectToRoute('student_exams');
        }

        return $this->redirectToRoute('app_login');
    }
}
