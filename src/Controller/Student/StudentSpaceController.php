<?php

namespace App\Controller\Student;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/student')]
class StudentSpaceController extends AbstractController
{
    #[Route('/courses', name: 'student_courses')]
    public function courses(): Response
    {
        $courses = [
            [
                'title' => 'Introduction à l\'Intelligence Artificielle',
                'instructor' => 'Dr. Dupont',
                'modules' => 2,
                'hours' => 4,
                'progress' => 65,
                'badge' => 'Continuer',
                'badge_color' => '#3B49A2',
                'badge_bg' => '#DBEAFE'
            ],
            [
                'title' => 'Développement Web et CSS Avancé',
                'instructor' => 'Mme Lefevre',
                'modules' => 8,
                'hours' => 6,
                'progress' => 0,
                'badge' => 'GRATUIT',
                'badge_color' => '#10B981',
                'badge_bg' => '#D1FAE5'
            ],
            [
                'title' => 'Algèbre Linéaire pour Débutants',
                'instructor' => 'Prof. Bernard',
                'modules' => 10,
                'hours' => 8,
                'progress' => 0,
                'badge' => 'GRATUIT',
                'badge_color' => '#10B981',
                'badge_bg' => '#D1FAE5'
            ],
            [
                'title' => 'Programmation Python',
                'instructor' => 'Niueeu Intermédiaire',
                'modules' => 12,
                'hours' => 10,
                'progress' => 40,
                'badge' => 'Premium',
                'badge_color' => '#B45309',
                'badge_bg' => '#FEF3C7'
            ],
            [
                'title' => 'Marketing Digital en 2024',
                'instructor' => 'Juile Cohen',
                'modules' => 7,
                'hours' => 5,
                'progress' => 0,
                'badge' => 'GRATUIT',
                'badge_color' => '#10B981',
                'badge_bg' => '#D1FAE5'
            ]
        ];

        return $this->render('student/course/index.html.twig', [
            'courses' => $courses
        ]);
    }

   
}
