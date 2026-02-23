<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgotPasswordType;
use App\Form\PasswordResetType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
     * Route pour la page de demande de réinitialisation de mot de passe (main)
     */
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_redirect_user');
        }

        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('success', 'Si cet email existe dans notre système, vous recevrez un lien de réinitialisation.');
                return $this->redirectToRoute('app_login');
            }

            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $user->setResetToken($token);
            $user->setResetTokenExpiresAt($expiresAt);

            $em->persist($user);
            $em->flush();

            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

            $emailMessage = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'noreply@edusmart.local')
                ->to($user->getEmail())
                ->subject('Réinitialiser votre mot de passe EduSmart')
                ->html(
                    $this->renderView('security/email/reset_password_email.html.twig', [
                        'user' => $user,
                        'resetUrl' => $resetUrl
                    ])
                );

            $emailSent = false;
            try {
                $mailer->send($emailMessage);
                $emailSent = true;
            } catch (TransportExceptionInterface $e) {
            }

            $this->addFlash('success', 'Si cet email existe dans notre système, vous recevrez un lien de réinitialisation.');
            if (!$emailSent) {
                $this->addFlash('reset_link_url', $resetUrl);
            }
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Route pour la réinitialisation du mot de passe (main)
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ): Response
    {
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
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $em->persist($user);
            $em->flush();

            $confirmEmail = (new Email())
                ->from('yassine.kaabi@esprit.tn')
                ->to($user->getEmail())
                ->subject('Votre mot de passe a été réinitialisé')
                ->html(
                    $this->renderView('security/email/password_reset_confirmation_email.html.twig', [
                        'user' => $user
                    ])
                );

            $mailer->send($confirmEmail);

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
        ]);
    }
}
