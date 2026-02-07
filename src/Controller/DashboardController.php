<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        $courses = [
            [
                'id' => 1,
                'title' => 'Introduction à l\'Intelligence Artificielle',
                'author' => 'Dr. Dupont',
                'modules' => 2,
                'duration' => '4 heures',
                'badge' => 'Continuer',
                'badge_type' => 'primary',
                'progress' => 65
            ],
            [
                'id' => 2,
                'title' => 'Développement Web et CSS Avancé',
                'author' => 'Mme Lefevre',
                'modules' => 8,
                'duration' => '6 heures',
                'badge' => 'GRATUIT',
                'badge_type' => 'success',
                'progress' => 0
            ],
            [
                'id' => 3,
                'title' => 'Algèbre Linéaire pour Débutants',
                'author' => 'Prof. Bernard',
                'modules' => 10,
                'duration' => '8 heures',
                'badge' => 'GRATUIT',
                'badge_type' => 'success',
                'progress' => 0
            ],
            [
                'id' => 4,
                'title' => 'Programmation Python',
                'author' => 'Niveau Intermédiaire',
                'modules' => 12,
                'duration' => '10 heures',
                'badge' => 'Premium',
                'badge_type' => 'warning',
                'progress' => 40
            ],
        ];

        return $this->render('dashboard/index.html.twig', [
            'courses' => $courses,
        ]);
    }
}
