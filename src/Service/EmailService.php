<?php

namespace App\Service;

use App\Entity\Bulletin;
use App\Entity\Certification;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $projectDir
    ) {}

    /**
     * Envoie le bulletin par email à l'étudiant
     */
    public function sendBulletinEmail(Bulletin $bulletin): void
    {
        $student = $bulletin->getStudent();
        if (!$student || !$student->getEmail()) {
            return;
        }

        $pdfPath = $bulletin->getPdfPath();
        if (!$pdfPath) {
            return;
        }

        $absolutePath = $this->projectDir . '/public/' . $pdfPath;
        if (!file_exists($absolutePath)) {
            return;
        }

        $email = (new Email())
            ->from('rachidgharbi09@gmail.com')
            ->to($student->getEmail())
            ->subject('📄 Votre Bulletin de Notes - EduSmart')
            ->html($this->getBulletinEmailTemplate($bulletin))
            ->attachFromPath($absolutePath, 'Bulletin_' . $bulletin->getAcademicYear() . '_' . $bulletin->getSemester() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    /**
     * Envoie la certification par email à l'étudiant
     */
    public function sendCertificationEmail(Certification $certification): void
    {
        $student = $certification->getStudent();
        if (!$student || !$student->getEmail()) {
            return;
        }

        $pdfPath = $certification->getPdfPath();
        if (!$pdfPath) {
            return;
        }

        $absolutePath = $this->projectDir . '/public/' . $pdfPath;
        if (!file_exists($absolutePath)) {
            return;
        }

        $email = (new Email())
            ->from('rachidgharbi09@gmail.com')
            ->to($student->getEmail())
            ->subject('🎓 Votre Certification Officielle - EduSmart')
            ->html($this->getCertificationEmailTemplate($certification))
            ->attachFromPath($absolutePath, 'Certification_' . $certification->getTypeLabel() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    private function getBulletinEmailTemplate(Bulletin $bulletin): string
    {
        $studentName = $bulletin->getStudent()->getPrenom() . ' ' . $bulletin->getStudent()->getName();
        $academicYear = $bulletin->getAcademicYear();
        $semester = $bulletin->getSemester();
        $average = number_format($bulletin->getAverage(), 2);
        $mention = $bulletin->getMention();
        $rank = $bulletin->getClassRank();

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: white; }
        .header { background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); padding: 40px; text-align: center; }
        .logo { font-size: 32px; font-weight: 900; color: white; letter-spacing: -1px; }
        .tagline { color: rgba(255,255,255,0.8); font-size: 12px; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px; }
        .content { padding: 40px; }
        .greeting { font-size: 24px; font-weight: 700; color: #1E293B; margin-bottom: 20px; }
        .message { color: #475569; line-height: 1.8; margin-bottom: 30px; }
        .info-card { background: linear-gradient(135deg, #F8FAFC 0%, #EEF2FF 100%); border-radius: 16px; padding: 25px; margin-bottom: 30px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #E2E8F0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748B; font-weight: 600; }
        .info-value { color: #1E293B; font-weight: 700; }
        .highlight { background: linear-gradient(135deg, #4F46E5, #7C3AED); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 28px; }
        .footer { background: #F1F5F9; padding: 30px; text-align: center; color: #64748B; font-size: 12px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #4F46E5, #7C3AED); color: white; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: 700; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EduSmart</div>
            <div class="tagline">Excellence Académique</div>
        </div>
        <div class="content">
            <div class="greeting">Bonjour {$studentName},</div>
            <div class="message">
                Nous avons le plaisir de vous transmettre votre bulletin de notes pour la période académique <strong>{$academicYear}</strong> - <strong>{$semester}</strong>.
            </div>
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Année Académique</span>
                    <span class="info-value">{$academicYear}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Semestre</span>
                    <span class="info-value">{$semester}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Moyenne Générale</span>
                    <span class="info-value highlight">{$average}/20</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mention</span>
                    <span class="info-value">{$mention}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Rang</span>
                    <span class="info-value">{$rank}</span>
                </div>
            </div>
            <div class="message">
                📎 Votre bulletin officiel est joint à cet email au format PDF. Ce document est authentifié et vérifiable via le QR code inclus.
            </div>
        </div>
        <div class="footer">
            © 2026 EduSmart Academy - Tous droits réservés<br>
            Ce message a été envoyé automatiquement, merci de ne pas y répondre.
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getCertificationEmailTemplate(Certification $certification): string
    {
        $studentName = $certification->getStudent()->getPrenom() . ' ' . $certification->getStudent()->getName();
        $certType = $certification->getTypeLabel();
        $uniqueNumber = $certification->getUniqueNumber();
        $verificationCode = $certification->getVerificationCode();
        $issuedAt = $certification->getIssuedAt()->format('d/m/Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: white; }
        .header { background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); padding: 40px; text-align: center; }
        .logo { font-size: 32px; font-weight: 900; color: white; letter-spacing: -1px; }
        .tagline { color: rgba(255,255,255,0.8); font-size: 12px; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px; }
        .badge { display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 8px 20px; border-radius: 50px; font-size: 12px; font-weight: 700; margin-top: 15px; }
        .content { padding: 40px; }
        .greeting { font-size: 24px; font-weight: 700; color: #1E293B; margin-bottom: 20px; }
        .message { color: #475569; line-height: 1.8; margin-bottom: 30px; }
        .cert-card { background: linear-gradient(135deg, #FAFBFF 0%, #EEF2FF 100%); border: 2px solid #E0E7FF; border-radius: 20px; padding: 30px; margin-bottom: 30px; text-align: center; }
        .cert-type { font-size: 22px; font-weight: 800; color: #4F46E5; margin-bottom: 15px; }
        .cert-number { font-family: monospace; background: #F1F5F9; padding: 10px 20px; border-radius: 10px; display: inline-block; color: #64748B; }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #E2E8F0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748B; font-weight: 600; }
        .info-value { color: #1E293B; font-weight: 700; }
        .footer { background: #F1F5F9; padding: 30px; text-align: center; color: #64748B; font-size: 12px; }
        .icon-congrats { font-size: 48px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EduSmart</div>
            <div class="tagline">Excellence Académique</div>
            <div class="badge">🎓 Certification Officielle</div>
        </div>
        <div class="content">
            <div class="icon-congrats">🏆</div>
            <div class="greeting">Félicitations {$studentName} !</div>
            <div class="message">
                Nous avons le plaisir de vous informer que votre certification a été émise avec succès. Ce document officiel atteste de votre réussite académique.
            </div>
            <div class="cert-card">
                <div class="cert-type">{$certType}</div>
                <div class="cert-number">N° {$uniqueNumber}</div>
            </div>
            <div style="background: #F8FAFC; border-radius: 12px; padding: 20px;">
                <div class="info-row">
                    <span class="info-label">Date d'émission</span>
                    <span class="info-value">{$issuedAt}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Code de vérification</span>
                    <span class="info-value" style="font-family: monospace;">{$verificationCode}</span>
                </div>
            </div>
            <div class="message" style="margin-top: 30px;">
                📎 Votre certification officielle est jointe à cet email au format PDF. Ce document est sécurisé par signature HMAC et vérifiable via le QR code inclus.
            </div>
        </div>
        <div class="footer">
            © 2026 EduSmart Academy - Tous droits réservés<br>
            Ce message a été envoyé automatiquement, merci de ne pas y répondre.
        </div>
    </div>
</body>
</html>
HTML;
    }
}
