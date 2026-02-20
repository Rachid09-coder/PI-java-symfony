<?php

namespace App\Controller\Student;

use App\Repository\BulletinRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/student/bulletins')]
class StudentBulletinController extends AbstractController
{
    #[Route('/', name: 'student_bulletins_index')]
    public function index(BulletinRepository $bulletinRepo): Response
    {
        $user = $this->getUser();
        
        // Récupérer uniquement les bulletins de l'étudiant connecté
        $bulletins = $bulletinRepo->findBy(
            ['student' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('student/bulletin/index.html.twig', [
            'bulletins' => $bulletins,
        ]);
    }
}
