<?php

namespace App\Service;

use App\Entity\Bulletin;
use App\Entity\Certification;
use App\Entity\User;
use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;

/**
 * Service d'envoi de SMS via Twilio
 */
class SmsService
{
    private ?Client $client = null;
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private bool $enabled;

    public function __construct(
        string $twilioAccountSid = '',
        string $twilioAuthToken = '',
        string $twilioFromNumber = '',
        private ?LoggerInterface $logger = null
    ) {
        $this->accountSid = $twilioAccountSid ?: ($_ENV['TWILIO_ACCOUNT_SID'] ?? '');
        $this->authToken = $twilioAuthToken ?: ($_ENV['TWILIO_AUTH_TOKEN'] ?? '');
        $this->fromNumber = $twilioFromNumber ?: ($_ENV['TWILIO_FROM_NUMBER'] ?? '');
        $this->enabled = !empty($this->accountSid) && !empty($this->authToken) && !empty($this->fromNumber);
    }

    /**
     * Vérifie si le service est configuré
     */
    public function isConfigured(): bool
    {
        return $this->enabled;
    }

    /**
     * Obtient le client Twilio
     */
    private function getClient(): Client
    {
        if ($this->client === null) {
            if (!$this->isConfigured()) {
                throw new \RuntimeException('Service SMS non configuré. Ajoutez TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN et TWILIO_FROM_NUMBER dans .env');
            }
            $this->client = new Client($this->accountSid, $this->authToken);
        }
        return $this->client;
    }

    /**
     * Envoie un SMS
     */
    public function sendSms(string $toNumber, string $message): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Service SMS non configuré',
            ];
        }

        // Nettoyer le numéro de téléphone
        $toNumber = $this->formatPhoneNumber($toNumber);

        if (empty($toNumber)) {
            return [
                'success' => false,
                'error' => 'Numéro de téléphone invalide',
            ];
        }

        try {
            $result = $this->getClient()->messages->create(
                $toNumber,
                [
                    'from' => $this->fromNumber,
                    'body' => $message,
                ]
            );

            $this->logger?->info('SMS envoyé', [
                'to' => $toNumber,
                'sid' => $result->sid,
                'status' => $result->status,
            ]);

            return [
                'success' => true,
                'messageSid' => $result->sid,
                'status' => $result->status,
                'to' => $toNumber,
            ];
        } catch (\Exception $e) {
            $this->logger?->error('Erreur envoi SMS', [
                'to' => $toNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Notifie un étudiant que son bulletin est prêt
     */
    public function notifyBulletinReady(Bulletin $bulletin): array
    {
        $student = $bulletin->getStudent();
        if (!$student) {
            return ['success' => false, 'error' => 'Étudiant non trouvé'];
        }

        $phone = $student->getPhone();
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Numéro de téléphone non renseigné'];
        }

        $message = sprintf(
            "EduSmart - Bonjour %s, votre bulletin %s %s est maintenant disponible. Connectez-vous à votre espace étudiant pour le consulter.",
            $student->getPrenom(),
            $bulletin->getSemester(),
            $bulletin->getAcademicYear()
        );

        return $this->sendSms($phone, $message);
    }

    /**
     * Notifie un étudiant que sa certification est prête
     */
    public function notifyCertificationReady(Certification $certification): array
    {
        $student = $certification->getStudent();
        if (!$student) {
            return ['success' => false, 'error' => 'Étudiant non trouvé'];
        }

        $phone = $student->getPhone();
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Numéro de téléphone non renseigné'];
        }

        $message = sprintf(
            "EduSmart - Bonjour %s, votre %s (N°%s) est prête. Code de vérification: %s. Connectez-vous pour télécharger le PDF.",
            $student->getPrenom(),
            $certification->getTypeLabel(),
            $certification->getUniqueNumber(),
            $certification->getVerificationCode()
        );

        return $this->sendSms($phone, $message);
    }

    /**
     * Envoie un rappel de validation en attente
     */
    public function sendValidationReminder(User $student, string $documentType, int $count): array
    {
        $phone = $student->getPhone();
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Numéro de téléphone non renseigné'];
        }

        $message = sprintf(
            "EduSmart - Bonjour %s, vous avez %d %s en attente de consultation. Connectez-vous à votre espace étudiant.",
            $student->getPrenom(),
            $count,
            $documentType
        );

        return $this->sendSms($phone, $message);
    }

    /**
     * Formate le numéro de téléphone au format E.164
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprimer les espaces, tirets, points
        $phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);

        // Si le numéro commence par 0, le remplacer par +33 (France)
        if (str_starts_with($phone, '0')) {
            $phone = '+33' . substr($phone, 1);
        }

        // S'assurer que le numéro commence par +
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        // Vérifier le format basique
        if (!preg_match('/^\+[1-9]\d{6,14}$/', $phone)) {
            return '';
        }

        return $phone;
    }
}
