<?php

namespace App\Service;

use App\Repository\CertificationRepository;

class VerificationCodeService
{
    public function __construct(private CertificationRepository $certificationRepo)
    {
    }

    /**
     * Génère un code de vérification unique pour un bulletin (hex)
     */
    public function generateForBulletin(): string
    {
        return strtoupper(bin2hex(random_bytes(8)));
    }

    /**
     * Génère un numéro unique pour une certification
     * Format: EDU-YYYY-XX-NNNNNN (ex: EDU-2026-SC-000231)
     */
    public function generateForCertification(string $type): string
    {
        $year = date('Y');
        $prefix = match($type) {
            'SCOLARITE' => 'SC',
            'REUSSITE'  => 'RE',
            'NOTES'     => 'RN',
            'DIPLOME'   => 'DI',
            'STAGE'     => 'ST',
            'PRESENCE'  => 'PR',
            default     => 'XX',
        };

        // Trouver le prochain numéro séquentiel
        $count = $this->certificationRepo->count([]) + 1;
        $seq = str_pad((string)$count, 6, '0', STR_PAD_LEFT);

        return sprintf('EDU-%s-%s-%s', $year, $prefix, $seq);
    }

    /**
     * Génère un code de vérification unique (pour bulletin ou certification)
     */
    public function generateVerificationCode(): string
    {
        return strtoupper(bin2hex(random_bytes(6)));
    }
}
