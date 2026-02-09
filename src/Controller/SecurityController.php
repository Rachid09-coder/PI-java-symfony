<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Route principale du site (/)
     */
    #[Route('/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, on l'envoie vers sa page de redirection
        if ($this->getUser()) {
            return $this->redirectToRoute('app_redirect_user');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Gare de triage : dirige vers le bon dashboard selon le rôle en BDD
     */
    #[Route('/redirect-user', name: 'app_redirect_user')]
    public function redirectUser(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérification de ton champ 'role' (professeur ou etudiant)
        if ($user->getRole() === 'professeur') {
            return $this->redirectToRoute('admin_shop_index');
        }

        return $this->redirectToRoute('student_shop_index');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Géré automatiquement par Symfony
    }
    
}