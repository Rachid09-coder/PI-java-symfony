<?php

namespace App\Service;

use App\Entity\Bulletin;
use App\Entity\User;

class BulletinWorkflowService
{
    private const TRANSITIONS = [
        'Brouillon' => 'Vérifié',
        'Vérifié'   => 'Validé',
        'Validé'    => 'Publié',
    ];

    public function __construct(
        private HmacService $hmacService,
        private VerificationCodeService $verificationCodeService,
        private AuditService $auditService,
    ) {
    }

    /**
     * Vérifie si une transition est possible
     */
    public function canTransition(Bulletin $bulletin, string $targetStatus): bool
    {
        $current = $bulletin->getStatus();
        if ($bulletin->isRevoked()) return false;
        return isset(self::TRANSITIONS[$current]) && self::TRANSITIONS[$current] === $targetStatus;
    }

    /**
     * Brouillon → Vérifié
     */
    public function verify(Bulletin $bulletin, User $performedBy): void
    {
        if (!$this->canTransition($bulletin, 'Vérifié')) {
            throw new \LogicException("Impossible de passer ce bulletin en 'Vérifié'. Statut actuel : " . $bulletin->getStatus());
        }

        $bulletin->setStatus('Vérifié');
        $bulletin->setUpdatedAt(new \DateTimeImmutable());

        $this->auditService->log('Bulletin', $bulletin->getId(), 'VERIFIED', $performedBy, [
            'previous_status' => 'Brouillon',
        ]);
    }

    /**
     * Vérifié → Validé
     */
    public function validate(Bulletin $bulletin, User $performedBy): void
    {
        if (!$this->canTransition($bulletin, 'Validé')) {
            throw new \LogicException("Impossible de valider ce bulletin. Statut actuel : " . $bulletin->getStatus());
        }

        $bulletin->setStatus('Validé');
        $bulletin->setValidatedBy($performedBy);
        $bulletin->setValidatedAt(new \DateTimeImmutable());
        $bulletin->setUpdatedAt(new \DateTimeImmutable());

        $this->auditService->log('Bulletin', $bulletin->getId(), 'VALIDATED', $performedBy, [
            'previous_status' => 'Vérifié',
        ]);
    }

    /**
     * Validé → Publié (génère HMAC + code de vérification)
     */
    public function publish(Bulletin $bulletin, User $performedBy): void
    {
        if (!$this->canTransition($bulletin, 'Publié')) {
            throw new \LogicException("Impossible de publier ce bulletin. Statut actuel : " . $bulletin->getStatus());
        }

        // Calcul automatique de la moyenne et de la mention
        if ($bulletin->getReportCardLines()->count() > 0) {
            $bulletin->setAverage($bulletin->computeAverage());
        }
        $bulletin->setMention($bulletin->computeMention());

        // Génération du code de vérification si absent
        if (!$bulletin->getVerificationCode()) {
            $bulletin->setVerificationCode($this->verificationCodeService->generateForBulletin());
        }

        // Signature HMAC anti-fraude
        $bulletin->setHmacHash($this->hmacService->signBulletin($bulletin));

        // Marquage publié
        $bulletin->setStatus('Publié');
        $bulletin->setPublishedBy($performedBy);
        $bulletin->setPublishedAt(new \DateTimeImmutable());
        $bulletin->setUpdatedAt(new \DateTimeImmutable());

        $this->auditService->log('Bulletin', $bulletin->getId(), 'PUBLISHED', $performedBy, [
            'previous_status' => 'Validé',
            'verification_code' => $bulletin->getVerificationCode(),
        ]);
    }

    /**
     * Révoquer un bulletin publié
     */
    public function revoke(Bulletin $bulletin, User $performedBy, string $reason): void
    {
        if (!$bulletin->isPublished()) {
            throw new \LogicException("Seuls les bulletins publiés peuvent être révoqués.");
        }

        $bulletin->setRevokedAt(new \DateTimeImmutable());
        $bulletin->setRevocationReason($reason);
        $bulletin->setUpdatedAt(new \DateTimeImmutable());

        $this->auditService->log('Bulletin', $bulletin->getId(), 'REVOKED', $performedBy, [
            'reason' => $reason,
        ]);
    }
}
