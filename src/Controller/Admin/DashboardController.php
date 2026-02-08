<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $students = $userRepository->findBy(['role' => 'etudiant'], ['id' => 'DESC']);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'total_students' => count($students),
                'total_courses' => 45,
                'total_exams' => 12,
                'revenue' => '15,400 €',
            ],
            'students' => $students,
        ]);
    }
   #[Route('/students/{id}/delete', name: 'admin_student_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $student = $userRepository->find($id);

        if (!$student || $student->getRole() !== 'etudiant') {
            throw $this->createNotFoundException();
        }

        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete_student_'.$student->getId(), $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $em->remove($student);
        $em->flush();

        $this->addFlash('success', 'Élève supprimé.');
        return $this->redirectToRoute('admin_dashboard');
    }
    #[Route('/students/{id}/edit', name: 'admin_student_edit', methods: ['GET', 'POST'])]
public function editStudent(
    User $student,
    Request $request,
    EntityManagerInterface $em
): Response {
    if ($student->getRole() !== 'etudiant') {
        throw $this->createNotFoundException();
    }

    $form = $this->createForm(UserType::class, $student);
    $form->remove('password'); // pas de changement mdp ici

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Élève modifié.');
        return $this->redirectToRoute('admin_dashboard');
    }

    return $this->render('admin/user/form.html.twig', [
        'user' => $student,
        'form' => $form->createView(),
    ]);
}
    #[Route('/courses/manage', name: 'admin_courses_manage', methods: ['GET'])]
public function manageCourses(): Response
{
    return $this->render('admin/course/manage.html.twig');
}

#[Route('/modules/manage', name: 'admin_modules_manage', methods: ['GET'])]
public function manageModules(): Response
{
    return $this->render('admin/module/manage.html.twig');
}

#[Route('/exams/manage', name: 'admin_exams_manage', methods: ['GET'])]
public function manageExams(): Response
{
    return $this->render('admin/exam/manage.html.twig');
}

}
