<?php

namespace App\Service;

use App\Entity\Bulletin;
use App\Entity\Certification;

class HmacService
{
    private string $secret;

    public function __construct(string $hmacSecret = 'edusmart-secret-key-change-me')
    {
        $this->secret = $hmacSecret;
    }

    /**
     * Génère un HMAC-SHA256 pour un bulletin
     */
    public function signBulletin(Bulletin $bulletin): string
    {
        $data = sprintf(
            '%d|%s|%s|%s|%.2f|%s',
            $bulletin->getStudent()?->getId(),
            $bulletin->getStudent()?->getName(),
            $bulletin->getAcademicYear(),
            $bulletin->getSemester(),
            $bulletin->getAverage(),
            $this->serializeLines($bulletin)
        );

        return hash_hmac('sha256', $data, $this->secret);
    }

    /**
     * Génère un HMAC-SHA256 pour une certification
     */
    public function signCertification(Certification $certification): string
    {
        $data = sprintf(
            '%d|%s|%s|%s|%s',
            $certification->getStudent()?->getId(),
            $certification->getType(),
            $certification->getUniqueNumber(),
            $certification->getVerificationCode(),
            $certification->getIssuedAt()?->format('Y-m-d')
        );

        return hash_hmac('sha256', $data, $this->secret);
    }

    /**
     * Vérifie l'intégrité d'un bulletin
     */
    public function verifyBulletin(Bulletin $bulletin): bool
    {
        if (!$bulletin->getHmacHash()) {
            return false;
        }
        return hash_equals($bulletin->getHmacHash(), $this->signBulletin($bulletin));
    }

    /**
     * Vérifie l'intégrité d'une certification
     */
    public function verifyCertification(Certification $certification): bool
    {
        if (!$certification->getHmacHash()) {
            return false;
        }
        return hash_equals($certification->getHmacHash(), $this->signCertification($certification));
    }

    private function serializeLines(Bulletin $bulletin): string
    {
        $parts = [];
        foreach ($bulletin->getReportCardLines() as $line) {
            $parts[] = sprintf('%s:%.2f:%.2f', $line->getModuleName(), $line->getNote(), $line->getCoefficient());
        }
        sort($parts);
        return implode('|', $parts);
    }
}
