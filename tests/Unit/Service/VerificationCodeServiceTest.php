<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\CertificationRepository;
use App\Service\VerificationCodeService;
use PHPUnit\Framework\TestCase;

/**
 * Tests du service de génération de codes de vérification.
 * Logique métier : format bulletin (hex 16 car.), certification (EDU-YYYY-PREFIX-NNNNNN), mapping des types.
 */
final class VerificationCodeServiceTest extends TestCase
{
    public function testGenerateForBulletinReturns16UppercaseHexCharacters(): void
    {
        $repo = $this->createMock(CertificationRepository::class);
        $service = new VerificationCodeService($repo);

        $code = $service->generateForBulletin();

        self::assertSame(16, strlen($code));
        self::assertMatchesRegularExpression('/^[A-F0-9]+$/', $code);
    }

    public function testGenerateForBulletinIsUnique(): void
    {
        $repo = $this->createMock(CertificationRepository::class);
        $service = new VerificationCodeService($repo);

        $codes = [$service->generateForBulletin(), $service->generateForBulletin()];
        self::assertNotSame($codes[0], $codes[1]);
    }

    public function testGenerateForCertificationFormatScolarite(): void
    {
        $repo = $this->createMock(CertificationRepository::class);
        $repo->method('count')->willReturn(42);
        $service = new VerificationCodeService($repo);

        $number = $service->generateForCertification('SCOLARITE');

        self::assertStringStartsWith('EDU-', $number);
        self::assertStringContainsString('-SC-', $number);
        self::assertMatchesRegularExpression('/^EDU-\d{4}-SC-\d{6}$/', $number);
        self::assertStringEndsWith('000043', $number);
    }

    public function testGenerateForCertificationFormatReussite(): void
    {
        $repo = $this->createMock(CertificationRepository::class);
        $repo->method('count')->willReturn(0);
        $service = new VerificationCodeService($repo);

        $number = $service->generateForCertification('REUSSITE');

        self::assertMatchesRegularExpression('/^EDU-\d{4}-RE-000001$/', $number);
    }

    public function testGenerateForCertificationTypeMapping(): void
    {
        $repo = $this->createMock(CertificationRepository::class);
        $repo->method('count')->willReturn(1);
        $service = new VerificationCodeService($repo);

        self::assertStringContainsString('-SC-', $service->generateForCertification('SCOLARITE'));
        self::assertStringContainsString('-RE-', $service->generateForCertification('REUSSITE'));
        self::assertStringContainsString('-RN-', $service->generateForCertification('NOTES'));
        self::assertStringContainsString('-DI-', $service->generateForCertification('DIPLOME'));
        self::assertStringContainsString('-ST-', $service->generateForCertification('STAGE'));
        self::assertStringContainsString('-PR-', $service->generateForCertification('PRESENCE'));
    }

    public function testGenerateForCertificationUnknownTypeUsesXX(): void
    {
        $repo = $this->createMock(CertificationRepository::class);
        $repo->method('count')->willReturn(0);
        $service = new VerificationCodeService($repo);

        $number = $service->generateForCertification('UNKNOWN');

        self::assertStringContainsString('-XX-', $number);
        self::assertMatchesRegularExpression('/^EDU-\d{4}-XX-000001$/', $number);
    }

    public function testGenerateVerificationCodeReturns12UppercaseHexCharacters(): void
    {
        $repo = $this->createMock(CertificationRepository::class);
        $service = new VerificationCodeService($repo);

        $code = $service->generateVerificationCode();

        self::assertSame(12, strlen($code));
        self::assertMatchesRegularExpression('/^[A-F0-9]+$/', $code);
    }
}
