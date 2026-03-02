<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Certification;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests de l'entité Certification : getTypeLabel (mapping type → libellé), isRevoked.
 */
final class CertificationTest extends TestCase
{
    private function createCertification(string $type = 'SCOLARITE'): Certification
    {
        $user = new User();
        $user->setName('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean@test.com');
        $user->setRole('etudiant');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $cert = new Certification();
        $cert->setStudent($user);
        $cert->setType($type);
        $cert->setVerificationCode('CODE123');
        $cert->setIssuedAt(new \DateTimeImmutable());
        return $cert;
    }

    public function testGetTypeLabelScolarite(): void
    {
        $cert = $this->createCertification('SCOLARITE');
        self::assertSame('Attestation de scolarité', $cert->getTypeLabel());
    }

    public function testGetTypeLabelReussite(): void
    {
        $cert = $this->createCertification('REUSSITE');
        self::assertSame('Certificat de réussite', $cert->getTypeLabel());
    }

    public function testGetTypeLabelNotes(): void
    {
        $cert = $this->createCertification('NOTES');
        self::assertSame('Relevé de notes', $cert->getTypeLabel());
    }

    public function testGetTypeLabelDiplome(): void
    {
        $cert = $this->createCertification('DIPLOME');
        self::assertSame('Diplôme interne', $cert->getTypeLabel());
    }

    public function testGetTypeLabelStage(): void
    {
        $cert = $this->createCertification('STAGE');
        self::assertSame('Attestation de stage', $cert->getTypeLabel());
    }

    public function testGetTypeLabelPresence(): void
    {
        $cert = $this->createCertification('PRESENCE');
        self::assertSame('Attestation de présence', $cert->getTypeLabel());
    }

    public function testGetTypeLabelUnknownReturnsType(): void
    {
        $cert = $this->createCertification('AUTRE');
        self::assertSame('AUTRE', $cert->getTypeLabel());
    }

    public function testIsRevokedWhenStatusRevoked(): void
    {
        $cert = $this->createCertification();
        $cert->setStatus('REVOKED');
        self::assertTrue($cert->isRevoked());
    }

    public function testIsRevokedWhenRevokedAtSet(): void
    {
        $cert = $this->createCertification();
        $cert->setStatus('ACTIVE');
        $cert->setRevokedAt(new \DateTimeImmutable());
        self::assertTrue($cert->isRevoked());
    }

    public function testIsRevokedFalseWhenActive(): void
    {
        $cert = $this->createCertification();
        $cert->setStatus('ACTIVE');
        $cert->setRevokedAt(null);
        self::assertFalse($cert->isRevoked());
    }
}
