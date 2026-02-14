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

final class AuthController extends AbstractController
{
    #[Route('/auth/login', name: 'app_auth_login', methods: ['GET'])]
    public function login(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if email already exists
            $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('danger', 'Cet email est déjà utilisé. Veuillez en choisir un autre.');
                return $this->render('auth/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $user->setPassword(
                $hasher->hashPassword($user, (string) $user->getPassword())
            );

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserType::class, $user);
        // Don't allow changing password and role from profile page
        if ($form->has('password')) {
            $form->remove('password');
        }
        if ($form->has('role')) {
            $form->remove('role');
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('auth/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/password', name: 'app_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Verify current password
            if (!$hasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('danger', 'Mot de passe actuel incorrect.');
                return $this->render('auth/change_password.html.twig');
            }

            // Check if passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('danger', 'Les nouveaux mots de passe ne correspondent pas.');
                return $this->render('auth/change_password.html.twig');
            }

            // Check password length
            if (strlen($newPassword) < 8) {
                $this->addFlash('danger', 'Le mot de passe doit faire au moins 8 caractères.');
                return $this->render('auth/change_password.html.twig');
            }

            // Check for uppercase letter
            if (!preg_match('/[A-Z]/', $newPassword)) {
                $this->addFlash('danger', 'Le mot de passe doit contenir au moins une majuscule.');
                return $this->render('auth/change_password.html.twig');
            }

            // Check for digit
            if (!preg_match('/[0-9]/', $newPassword)) {
                $this->addFlash('danger', 'Le mot de passe doit contenir au moins un chiffre.');
                return $this->render('auth/change_password.html.twig');
            }

            // Check for symbol
            if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $newPassword)) {
                $this->addFlash('danger', 'Le mot de passe doit contenir au moins un symbole (!@#$%^&* etc).');
                return $this->render('auth/change_password.html.twig');
            }

            // Hash and update password
            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('auth/change_password.html.twig');
    }
}
