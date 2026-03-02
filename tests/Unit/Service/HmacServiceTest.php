<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Bulletin;
use App\Entity\Certification;
use App\Entity\ReportCardLine;
use App\Entity\User;
use App\Service\HmacService;
use PHPUnit\Framework\TestCase;

/**
 * Tests du service HMAC (signature anti-fraude bulletins et certifications).
 * Logique métier : construction de la chaîne signée, vérification d'intégrité.
 */
final class HmacServiceTest extends TestCase
{
    private function createUser(int $id = 1, string $name = 'Dupont'): User
    {
        $user = new User();
        $user->setName($name);
        $user->setPrenom('Jean');
        $user->setEmail('jean@test.com');
        $user->setRole('etudiant');
        $user->setPassword('hash');
        $user->setNumtel('12345678');
        $ref = new \ReflectionClass($user);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($user, $id);
        return $user;
    }

    private function createBulletin(?User $student = null): Bulletin
    {
        $student = $student ?? $this->createUser(1, 'Dupont');
        $bulletin = new Bulletin();
        $bulletin->setStudent($student);
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');
        $bulletin->setAverage(14.5);
        $bulletin->setStatus('Publié');
        $bulletin->setMention('Bien');

        $line1 = new ReportCardLine();
        $line1->setModuleName('Maths');
        $line1->setNote(15.0);
        $line1->setCoefficient(2.0);
        $bulletin->addReportCardLine($line1);

        $line2 = new ReportCardLine();
        $line2->setModuleName('Français');
        $line2->setNote(14.0);
        $line2->setCoefficient(2.0);
        $bulletin->addReportCardLine($line2);

        return $bulletin;
    }

    public function testSignBulletinReturnsSha256Hex(): void
    {
        $service = new HmacService('test-secret');
        $bulletin = $this->createBulletin();

        $hash = $service->signBulletin($bulletin);

        self::assertSame(64, strlen($hash));
        self::assertMatchesRegularExpression('/^[a-f0-9]+$/', $hash);
    }

    public function testSignBulletinIsDeterministic(): void
    {
        $service = new HmacService('test-secret');
        $bulletin = $this->createBulletin();

        self::assertSame($service->signBulletin($bulletin), $service->signBulletin($bulletin));
    }

    public function testSignBulletinChangesWhenDataChanges(): void
    {
        $service = new HmacService('test-secret');
        $bulletin = $this->createBulletin();
        $hash1 = $service->signBulletin($bulletin);

        $bulletin->setAverage(12.0);
        $hash2 = $service->signBulletin($bulletin);

        self::assertNotSame($hash1, $hash2);
    }

    public function testVerifyBulletinReturnsTrueWhenHashMatches(): void
    {
        $service = new HmacService('test-secret');
        $bulletin = $this->createBulletin();
        $bulletin->setHmacHash($service->signBulletin($bulletin));

        self::assertTrue($service->verifyBulletin($bulletin));
    }

    public function testVerifyBulletinReturnsFalseWhenHashMissing(): void
    {
        $service = new HmacService('test-secret');
        $bulletin = $this->createBulletin();
        $bulletin->setHmacHash(null);

        self::assertFalse($service->verifyBulletin($bulletin));
    }

    public function testVerifyBulletinReturnsFalseWhenHashTampered(): void
    {
        $service = new HmacService('test-secret');
        $bulletin = $this->createBulletin();
        $bulletin->setHmacHash('0' . substr($service->signBulletin($bulletin), 1));

        self::assertFalse($service->verifyBulletin($bulletin));
    }

    public function testSignCertificationReturnsSha256Hex(): void
    {
        $student = $this->createUser(2);
        $cert = new Certification();
        $cert->setStudent($student);
        $cert->setType('SCOLARITE');
        $cert->setUniqueNumber('EDU-2026-SC-000001');
        $cert->setVerificationCode('ABC123');
        $cert->setIssuedAt(new \DateTimeImmutable('2026-01-15'));

        $service = new HmacService('test-secret');
        $hash = $service->signCertification($cert);

        self::assertSame(64, strlen($hash));
        self::assertMatchesRegularExpression('/^[a-f0-9]+$/', $hash);
    }

    public function testVerifyCertificationReturnsTrueWhenHashMatches(): void
    {
        $student = $this->createUser(2);
        $cert = new Certification();
        $cert->setStudent($student);
        $cert->setType('REUSSITE');
        $cert->setUniqueNumber('EDU-2026-RE-000001');
        $cert->setVerificationCode('XYZ');
        $cert->setIssuedAt(new \DateTimeImmutable('2026-01-15'));

        $service = new HmacService('secret');
        $cert->setHmacHash($service->signCertification($cert));

        self::assertTrue($service->verifyCertification($cert));
    }

    public function testVerifyCertificationReturnsFalseWhenHashMissing(): void
    {
        $cert = new Certification();
        $cert->setStudent($this->createUser(1));
        $cert->setType('SCOLARITE');
        $cert->setHmacHash(null);

        $service = new HmacService('secret');
        self::assertFalse($service->verifyCertification($cert));
    }
}
