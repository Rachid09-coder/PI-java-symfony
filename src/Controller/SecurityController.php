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
        if ($user->getRole() === 'admin') {
            return $this->redirectToRoute('admin_shop_index');
        }

        return $this->redirectToRoute('student_shop_index');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Géré automatiquement par Symfony
    }

    /**
     * Route pour la page de demande de réinitialisation de mot de passe
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

            // On envoie un message même si l'utilisateur n'existe pas pour éviter la fuite d'information
            if (!$user) {
                $this->addFlash('success', 'Si cet email existe dans notre système, vous recevrez un lien de réinitialisation.');
                return $this->redirectToRoute('app_login');
            }

            // Générer un token unique
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            
            // Définir l'expiration du token (1 heure)
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $user->setResetToken($token);
            $user->setResetTokenExpiresAt($expiresAt);

            $em->persist($user);
            $em->flush();

            // Envoyer l'email
            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            
            $email = (new Email())
                ->from('yassine.kaabi@esprit.tn')
                ->to($user->getEmail())
                ->subject('Réinitialiser votre mot de passe EduSmart')
                ->html(
                    $this->renderView('security/email/reset_password_email.html.twig', [
                        'user' => $user,
                        'resetUrl' => $resetUrl
                    ])
                );

            $mailer->send($email);

            $this->addFlash('success', 'Si cet email existe dans notre système, vous recevrez un lien de réinitialisation.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Route pour la réinitialisation du mot de passe
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

        // Vérifier si le token existe et n'a pas expiré
        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable('now')) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();

            // Hasher le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            // Nettoyer le token
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $em->persist($user);
            $em->flush();

            // Envoyer un email de confirmation
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