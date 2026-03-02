<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Bulletin;
use App\Entity\ReportCardLine;
use App\Entity\User;
use App\Service\AuditService;
use App\Service\BulletinWorkflowService;
use App\Service\HmacService;
use App\Service\VerificationCodeService;
use PHPUnit\Framework\TestCase;

/**
 * Tests du workflow des bulletins (Brouillon → Vérifié → Validé → Publié, révocation).
 * Logique métier : transitions autorisées, génération code/HMAC à la publication.
 */
final class BulletinWorkflowServiceTest extends TestCase
{
    private function createService(
        ?HmacService $hmac = null,
        ?VerificationCodeService $verification = null,
        ?AuditService $audit = null
    ): BulletinWorkflowService {
        return new BulletinWorkflowService(
            $hmac ?? $this->createMock(HmacService::class),
            $verification ?? $this->createMock(VerificationCodeService::class),
            $audit ?? $this->createMock(AuditService::class)
        );
    }

    private function createBulletin(string $status = 'Brouillon', ?int $id = 1): Bulletin
    {
        $user = new User();
        $user->setName('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean@test.com');
        $user->setRole('etudiant');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $bulletin = new Bulletin();
        $bulletin->setStudent($user);
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');
        $bulletin->setStatus($status);
        $bulletin->setMention('Passable');
        $bulletin->setAverage(10.5);
        if ($id !== null) {
            $ref = new \ReflectionClass($bulletin);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($bulletin, $id);
        }
        return $bulletin;
    }

    private function createPerformer(): User
    {
        $u = new User();
        $u->setName('Admin');
        $u->setPrenom('Test');
        $u->setEmail('admin@test.com');
        $u->setRole('admin');
        $u->setPassword('hash');
        $u->setNumtel('87654321');
        return $u;
    }

    public function testCanTransitionBrouillonToVerifie(): void
    {
        $service = $this->createService();
        $bulletin = $this->createBulletin('Brouillon');
        self::assertTrue($service->canTransition($bulletin, 'Vérifié'));
    }

    public function testCanTransitionVerifieToValide(): void
    {
        $service = $this->createService();
        $bulletin = $this->createBulletin('Vérifié');
        self::assertTrue($service->canTransition($bulletin, 'Validé'));
    }

    public function testCanTransitionValideToPublie(): void
    {
        $service = $this->createService();
        $bulletin = $this->createBulletin('Validé');
        self::assertTrue($service->canTransition($bulletin, 'Publié'));
    }

    public function testCanTransitionReturnsFalseForInvalidTarget(): void
    {
        $service = $this->createService();
        $bulletin = $this->createBulletin('Brouillon');
        self::assertFalse($service->canTransition($bulletin, 'Validé'));
        self::assertFalse($service->canTransition($bulletin, 'Publié'));
        self::assertFalse($service->canTransition($bulletin, 'Brouillon'));
    }

    public function testCanTransitionReturnsFalseWhenRevoked(): void
    {
        $service = $this->createService();
        $bulletin = $this->createBulletin('Publié');
        $bulletin->setRevokedAt(new \DateTimeImmutable());
        self::assertFalse($service->canTransition($bulletin, 'Vérifié'));
    }

    public function testVerifyUpdatesStatusAndCallsAudit(): void
    {
        $audit = $this->createMock(AuditService::class);
        $audit->expects(self::once())->method('log')->with('Bulletin', self::anything(), 'VERIFIED', self::anything(), self::anything());

        $service = $this->createService(null, null, $audit);
        $bulletin = $this->createBulletin('Brouillon');
        $performer = $this->createPerformer();

        $service->verify($bulletin, $performer);

        self::assertSame('Vérifié', $bulletin->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $bulletin->getUpdatedAt());
    }

    public function testVerifyThrowsWhenTransitionNotAllowed(): void
    {
        $service = $this->createService();
        $bulletin = $this->createBulletin('Validé');
        $performer = $this->createPerformer();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Impossible de passer ce bulletin en 'Vérifié'");
        $service->verify($bulletin, $performer);
    }

    public function testValidateUpdatesStatusAndValidatedBy(): void
    {
        $audit = $this->createMock(AuditService::class);
        $audit->expects(self::once())->method('log')->with('Bulletin', self::anything(), 'VALIDATED', self::anything(), self::anything());

        $service = $this->createService(null, null, $audit);
        $bulletin = $this->createBulletin('Vérifié');
        $performer = $this->createPerformer();

        $service->validate($bulletin, $performer);

        self::assertSame('Validé', $bulletin->getStatus());
        self::assertSame($performer, $bulletin->getValidatedBy());
        self::assertNotNull($bulletin->getValidatedAt());
    }

    public function testValidateThrowsWhenTransitionNotAllowed(): void
    {
        $service = $this->createService();
        $bulletin = $this->createBulletin('Brouillon');
        $performer = $this->createPerformer();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Impossible de valider ce bulletin');
        $service->validate($bulletin, $performer);
    }

    public function testPublishSetsVerificationCodeAndHmacWhenAbsent(): void
    {
        $verification = $this->createMock(VerificationCodeService::class);
        $verification->method('generateForBulletin')->willReturn('CODE123');
        $hmac = $this->createMock(HmacService::class);
        $hmac->method('signBulletin')->willReturn('hmac-hash');
        $audit = $this->createMock(AuditService::class);
        $audit->expects(self::once())->method('log');

        $service = $this->createService($hmac, $verification, $audit);
        $bulletin = $this->createBulletin('Validé');
        $bulletin->setVerificationCode(null);
        $line = new ReportCardLine();
        $line->setBulletin($bulletin);
        $line->setModuleName('Maths');
        $line->setNote(12.0);
        $line->setCoefficient(1.0);
        $bulletin->addReportCardLine($line);
        $performer = $this->createPerformer();

        $service->publish($bulletin, $performer);

        self::assertSame('CODE123', $bulletin->getVerificationCode());
        self::assertSame('hmac-hash', $bulletin->getHmacHash());
        self::assertSame('Publié', $bulletin->getStatus());
        self::assertSame($performer, $bulletin->getPublishedBy());
    }

    public function testPublishThrowsWhenTransitionNotAllowed(): void
    {
        $service = $this->createService();
        $bulletin = $this->createBulletin('Brouillon');
        $performer = $this->createPerformer();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Impossible de publier ce bulletin');
        $service->publish($bulletin, $performer);
    }

    public function testRevokeSetsRevokedAtAndReason(): void
    {
        $audit = $this->createMock(AuditService::class);
        $audit->expects(self::once())->method('log')->with('Bulletin', self::anything(), 'REVOKED', self::anything(), ['reason' => 'Erreur']);

        $service = $this->createService(null, null, $audit);
        $bulletin = $this->createBulletin('Publié');
        $performer = $this->createPerformer();

        $service->revoke($bulletin, $performer, 'Erreur');

        self::assertNotNull($bulletin->getRevokedAt());
        self::assertSame('Erreur', $bulletin->getRevocationReason());
    }

    public function testRevokeThrowsWhenNotPublished(): void
    {
        $service = $this->createService();
        $bulletin = $this->createBulletin('Validé');
        $performer = $this->createPerformer();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Seuls les bulletins publiés peuvent être révoqués');
        $service->revoke($bulletin, $performer, 'Raison');
    }
}
