<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/certification')]
class CertificationController extends AbstractController
{
    #[Route('/', name: 'admin_certification_index')]
    public function index(): Response
    {
        $certifications = [
            ['id' => 1, 'student' => 'Alice Martin', 'course' => 'Mathématiques', 'date' => '2026-01-15'],
            ['id' => 2, 'student' => 'Bob Durand', 'course' => 'Physique', 'date' => '2026-02-01'],
        ];

        return $this->render('admin/certification/index.html.twig', [
            'certifications' => $certifications,
        ]);
    }

    #[Route('/bulletin/new', name: 'admin_bulletin_new')]
    #[Route('/bulletin/{id}/edit', name: 'admin_bulletin_edit')]
    public function bulletinForm(?int $id = null): Response
    {
        return $this->render('admin/certification/bulletin_form.html.twig', [
            'id' => $id
        ]);
    }

    #[Route('/new', name: 'admin_certification_new')]
    #[Route('/{id}/edit', name: 'admin_certification_edit')]
    public function certForm(?int $id = null): Response
    {
        return $this->render('admin/certification/cert_form.html.twig', [
            'id' => $id
        ]);
    }
}
