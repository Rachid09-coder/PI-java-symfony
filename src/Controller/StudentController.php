<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StudentController extends AbstractController
{
    #[Route('/students', name: 'app_student_index')]
    public function index(): Response
    {
        $students = [
            ['id' => 1, 'name' => 'Alice Martin', 'class' => 'Terminale A', 'email' => 'alice@example.com', 'status' => 'Actif'],
            ['id' => 2, 'name' => 'Bob Durand', 'class' => 'Terminale B', 'email' => 'bob@example.com', 'status' => 'Actif'],
            ['id' => 3, 'name' => 'Charlie Leroy', 'class' => 'Première S', 'email' => 'charlie@example.com', 'status' => 'Inactif'],
            ['id' => 4, 'name' => 'David Petit', 'class' => 'Seconde C', 'email' => 'david@example.com', 'status' => 'Actif'],
            ['id' => 5, 'name' => 'Eva Morel', 'class' => 'Terminale A', 'email' => 'eva@example.com', 'status' => 'Actif'],
        ];

        return $this->render('student/index.html.twig', [
            'students' => $students,
        ]);
    }

    #[Route('/students/{id}', name: 'app_student_show')]
    public function show(int $id): Response
    {
        // Dummy data for a single student
        $student = [
            'id' => $id,
            'name' => 'Alice Martin',
            'class' => 'Terminale A',
            'email' => 'alice@example.com',
            'status' => 'Actif',
            'phone' => '+33 6 12 34 56 78',
            'address' => '123 Rue de l\'Éducation, 75000 Paris',
            'bio' => 'Alice est une élève brillante, particulièrement intéressée par les mathématiques et l\'intelligence artificielle.'
        ];

        return $this->render('student/show.html.twig', [
            'student' => $student,
        ]);
    }
}
