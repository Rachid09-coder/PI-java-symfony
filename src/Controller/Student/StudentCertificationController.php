<?php

namespace App\Controller\Student;

use App\Repository\BulletinRepository;
use App\Repository\CertificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/student/certifications')]
class StudentCertificationController extends AbstractController
{
    #[Route('/', name: 'student_certifications_index')]
    public function index(
        BulletinRepository $bulletinRepo,
        CertificationRepository $certificationRepo
    ): Response {
        $user = $this->getUser();
        
        return $this->render('student/certification/index.html.twig', [
            'bulletins' => $bulletinRepo->findBy(['student' => $user], ['createdAt' => 'DESC']),
            'certifications' => $certificationRepo->findBy(['student' => $user], ['issuedAt' => 'DESC']),
        ]);
    }
}
