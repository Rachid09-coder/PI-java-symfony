<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserCrudController extends AbstractController
{
    // LISTE des étudiants (role = etudiant) -> index.html.twig
    #[Route('/students', name: 'student_index', methods: ['GET'])]
    public function indexStudents(UserRepository $repo): Response
    {
        $students = $repo->findBy(['role' => 'etudiant'], ['id' => 'DESC']);

        return $this->render('index.html.twig', [
            'students' => $students,
        ]);
    }

    // CREATE étudiant -> register.html.twig
    #[Route('/students/new', name: 'student_new', methods: ['GET', 'POST'])]
    public function createStudent(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new User();
        $user->setRole('etudiant');

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, (string) $user->getPassword()));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Étudiant créé.');
            return $this->redirectToRoute('student_index');
        }

        return $this->render('register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // EDIT infos -> profile.html.twig
    #[Route('/students/{id}/edit', name: 'student_edit', methods: ['GET', 'POST'])]
public function editStudent(
    User $user,
    Request $request,
    EntityManagerInterface $em
): Response {
    if ($user->getRole() !== 'etudiant') {
        throw $this->createNotFoundException();
    }

    $form = $this->createForm(UserType::class, $user);
    $form->remove('password'); // on edit les infos sans password
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();

        $this->addFlash('success', 'Profil mis à jour.');
        return $this->redirectToRoute('student_index');
    }

    return $this->render('admin/user/form.html.twig', [
        'form' => $form->createView(),
        'user' => $user, // ✅ IMPORTANT: ton twig utilise user.id
    ]);
}


    // CHANGE PASSWORD -> change_password.html.twig
    #[Route('/students/{id}/password', name: 'student_password', methods: ['GET', 'POST'])]
    public function changeStudentPassword(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        if ($user->getRole() !== 'etudiant') {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            $newPassword = (string) $request->request->get('password');

            if (strlen($newPassword) < 6) {
                $this->addFlash('danger', 'Mot de passe trop court (min 6 caractères).');
            } elseif (!preg_match('/[A-Z]/', $newPassword)) {
                $this->addFlash('danger', 'Le mot de passe doit contenir au moins une lettre majuscule.');
            } else {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
                $em->flush();

                $this->addFlash('success', 'Mot de passe modifié.');
                return $this->redirectToRoute('student_edit', ['id' => $user->getId()]);
            }
        }

        return $this->render('change_password.html.twig', [
            'student' => $user,
        ]);
    }
}
