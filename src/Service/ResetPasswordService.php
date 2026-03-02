<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Handles secure password reset: token generation, persistence, and email.
 * Does not reveal whether an email exists (same outcome for valid/invalid email).
 */
final class ResetPasswordService
{
    private const TOKEN_EXPIRY_HOURS = 1;

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private string $mailerFrom,
    ) {
    }

    /**
     * Generates a cryptographically secure token (64 hex chars).
     */
    public function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * If the email belongs to a user: generates token, sets expiry, saves, and sends email.
     * Otherwise: does nothing. Never reveals whether the email exists.
     */
    public function requestPasswordReset(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            return;
        }

        $token = $this->generateSecureToken();
        $expiresAt = new \DateTimeImmutable('+' . self::TOKEN_EXPIRY_HOURS . ' hour');

        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiresAt);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->sendResetEmail($user, $token);
    }

    private function sendResetEmail(User $user, string $token): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Réinitialiser votre mot de passe - EduSmart')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'user' => $user,
                'token' => $token,
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // Log in production; do not reveal failure to user
            // (same UX as "email not found" to avoid enumeration)
        }
    }

    /**
     * Invalidates the reset token after successful password change.
     */
    public function clearResetToken(User $user): void
    {
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
