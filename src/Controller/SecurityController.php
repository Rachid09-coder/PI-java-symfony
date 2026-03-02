<?php

namespace App\Controller;

use App\Form\ForgotPasswordType;
use App\Form\PasswordResetType;
use App\Repository\UserRepository;
use App\Service\ResetPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

        // main : reCAPTCHA ; fedi-work : login simple — on garde les deux (clé reCAPTCHA pour le formulaire)
        $recaptchaSiteKey = $this->getParameter('recaptcha_site_key');
        if ($recaptchaSiteKey === '' || $recaptchaSiteKey === null) {
            $recaptchaSiteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
            'recaptcha_site_key' => $recaptchaSiteKey,
        ]);
    }

    /**
     * Gare de triage : dirige vers le bon espace selon le rôle (main: admin_dashboard/student_courses ; fedi: admin_shop/student_shop)
     */
    #[Route('/redirect-user', name: 'app_redirect_user')]
    public function redirectUser(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Prof, admin ou chef de département → espace admin (main)
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_PROFESSEUR') || $this->isGranted('ROLE_CHEF_DEPT')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        // Étudiant → espace étudiant (main)
        return $this->redirectToRoute('student_courses');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Géré automatiquement par Symfony
    }

    /**
     * Forgot password: same message whether email exists or not (no user enumeration).
     */
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, ResetPasswordService $resetPasswordService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_redirect_user');
        }

        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $resetPasswordService->requestPasswordReset($email);

            $this->addFlash('success', 'Si cet email existe dans notre système, vous recevrez un lien de réinitialisation par email.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Reset password: validate token and expiry, then show form. On submit: hash password, clear token, redirect to login.
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        ResetPasswordService $resetPasswordService,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_redirect_user');
        }

        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable('now')) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $resetPasswordService->clearResetToken($user);

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
        ]);
    }
}
