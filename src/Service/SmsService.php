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
    /** Indicatif pays par défaut pour numéros à 8 chiffres (ex: 216 pour Tunisie). Optionnel. */
    private string $defaultCountryCode;

    public function __construct(
        string $twilioAccountSid = '',
        string $twilioAuthToken = '',
        string $twilioFromNumber = '',
        string $twilioDefaultCountryCode = '',
        private ?LoggerInterface $logger = null
    ) {
        $this->accountSid = $twilioAccountSid ?: ($_ENV['TWILIO_ACCOUNT_SID'] ?? '');
        $this->authToken = $twilioAuthToken ?: ($_ENV['TWILIO_AUTH_TOKEN'] ?? '');
        $this->fromNumber = $twilioFromNumber ?: ($_ENV['TWILIO_FROM_NUMBER'] ?? '');
        $this->defaultCountryCode = preg_replace('/\D/', '', $twilioDefaultCountryCode ?: ($_ENV['TWILIO_DEFAULT_COUNTRY_CODE'] ?? ''));
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

            $message = $e->getMessage();
            if (str_contains($message, 'Invalid') && (str_contains($message, 'To') || str_contains($message, 'Phone'))) {
                $message = 'Numéro de téléphone invalide ou non pris en charge par le service SMS. '
                    . 'Vérifiez que le numéro est un mobile au format international (ex: 06 12 34 56 78 pour la France).';
            }

            return [
                'success' => false,
                'error' => $message,
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
     * Formate le numéro de téléphone au format E.164 pour Twilio.
     * - Chiffres uniquement, préfixe national (0) après l'indicatif pays retiré.
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Ne garder que les chiffres (et remplacer O/l par 0/1 pour erreurs de saisie)
        $phone = str_replace(['O', 'o', 'l', 'I'], ['0', '0', '1', '1'], $phone);
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '' || strlen($digits) < 8 || strlen($digits) > 15) {
            return '';
        }

        // France : 0X XX XX XX XX -> +33 X XX XX XX XX (9 chiffres après 33)
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '33' . substr($digits, 1);
        }
        if (str_starts_with($digits, '0')) {
            return '';
        }

        // Numéro à 8 chiffres sans indicatif : utiliser l'indicatif par défaut si configuré (ex: Tunisie 216)
        if (strlen($digits) === 8 && $this->defaultCountryCode !== '') {
            $digits = $this->defaultCountryCode . $digits;
        }

        // E.164 : pas de 0 après l'indicatif pays (préfixe national). Twilio rejette sinon.
        // Indicatif 2 chiffres (33, 95, 44…) : retirer le 0 en 3e position si présent.
        if (strlen($digits) >= 11 && strlen($digits) <= 15 && isset($digits[2]) && $digits[2] === '0') {
            $digits = substr($digits, 0, 2) . substr($digits, 3);
        }
        // Indicatif 3 chiffres (216, 213…) : retirer le 0 en 4e position si présent.
        if (strlen($digits) >= 11 && strlen($digits) <= 15 && isset($digits[3]) && $digits[3] === '0') {
            $digits = substr($digits, 0, 3) . substr($digits, 4);
        }

        $e164 = '+' . $digits;

        // E.164 : + suivi de 7 à 15 chiffres (indicatif pays 1–3 chiffres puis numéro)
        if (!preg_match('/^\+[1-9]\d{6,14}$/', $e164)) {
            return '';
        }

        return $e164;
    }

    /**
     * Vérifie si un numéro est valide pour l'envoi SMS (sans envoyer).
     */
    public function isPhoneValidForSms(string $phone): bool
    {
        return $this->formatPhoneNumber($phone) !== '';
    }
}
