<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ResetPasswordService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Tests du service de réinitialisation de mot de passe.
 * Logique métier : génération de token sécurisé, expiration (1h), invalidation après reset.
 * Pas de test des contrôleurs ni du framework.
 */
final class ResetPasswordServiceTest extends TestCase
{
    public function testGenerateSecureTokenReturns64HexCharacters(): void
    {
        $service = new ResetPasswordService(
            $this->createMock(UserRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(MailerInterface::class),
            'noreply@test.com'
        );

        $token = $service->generateSecureToken();

        self::assertSame(64, strlen($token));
        self::assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testGenerateSecureTokenIsUniqueEachTime(): void
    {
        $service = new ResetPasswordService(
            $this->createMock(UserRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(MailerInterface::class),
            'noreply@test.com'
        );

        $tokens = [$service->generateSecureToken(), $service->generateSecureToken(), $service->generateSecureToken()];

        self::assertCount(3, array_unique($tokens));
    }

    public function testRequestPasswordResetDoesNothingWhenEmailNotFound(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findOneBy')->with(['email' => 'unknown@test.com'])->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::never())->method('flush');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $service = new ResetPasswordService($userRepo, $em, $mailer, 'noreply@test.com');
        $service->requestPasswordReset('unknown@test.com');
    }

    public function testRequestPasswordResetSetsTokenAndExpiryAndSendsEmailWhenUserExists(): void
    {
        $user = new User();
        $user->setName('Test');
        $user->setPrenom('User');
        $user->setEmail('user@test.com');
        $user->setRole('etudiant');
        $user->setPassword('hashed');
        $user->setNumtel('12345678');

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findOneBy')->with(['email' => 'user@test.com'])->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::once())->method('flush');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with(self::anything());

        $service = new ResetPasswordService($userRepo, $em, $mailer, 'noreply@test.com');
        $service->requestPasswordReset('user@test.com');

        self::assertNotNull($user->getResetToken());
        self::assertSame(64, strlen($user->getResetToken()));
        self::assertMatchesRegularExpression('/^[a-f0-9]+$/', $user->getResetToken());
        self::assertInstanceOf(\DateTimeImmutable::class, $user->getResetTokenExpiresAt());
        self::assertGreaterThan(new \DateTimeImmutable(), $user->getResetTokenExpiresAt());
    }

    public function testClearResetTokenNullifiesTokenAndExpiry(): void
    {
        $user = new User();
        $user->setName('Test');
        $user->setPrenom('User');
        $user->setEmail('user@test.com');
        $user->setRole('etudiant');
        $user->setPassword('hashed');
        $user->setNumtel('12345678');
        $user->setResetToken('abc123');
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::once())->method('flush');

        $service = new ResetPasswordService(
            $this->createMock(UserRepository::class),
            $em,
            $this->createMock(MailerInterface::class),
            'noreply@test.com'
        );
        $service->clearResetToken($user);

        self::assertNull($user->getResetToken());
        self::assertNull($user->getResetTokenExpiresAt());
    }
}
