<?php

namespace App\Service;

use App\Entity\Bulletin;
use App\Entity\Certification;
use App\Repository\SignatureAssetRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGeneratorService
{
    private string $projectDir;

    public function __construct(
        private Environment $twig,
        private QrCodeService $qrCodeService,
        private SignatureAssetRepository $signatureAssetRepo,
        string $projectDir
    ) {
        $this->projectDir = $projectDir;
    }

    /**
     * Génère le PDF d'un bulletin et retourne le chemin relatif
     */
    public function generateBulletinPdf(Bulletin $bulletin, string $baseUrl): string
    {
        // Génération du QR code
        $verifyUrl = $baseUrl . '/verify/' . $bulletin->getVerificationCode();
        $qrBase64 = $this->qrCodeService->generateBase64($verifyUrl);

        // Récupérer les assets de signature
        $logos = $this->signatureAssetRepo->findByType('logo');
        $signatures = $this->signatureAssetRepo->findByType('signature');
        $cachets = $this->signatureAssetRepo->findByType('cachet');

        // Rendu Twig
        $html = $this->twig->render('pdf/bulletin.html.twig', [
            'bulletin' => $bulletin,
            'qrBase64' => $qrBase64,
            'verifyUrl' => $verifyUrl,
            'logo' => $logos[0] ?? null,
            'signature' => $signatures[0] ?? null,
            'cachet' => $cachets[0] ?? null,
            'projectDir' => $this->projectDir,
        ]);

        // Génération PDF avec Dompdf
        $filename = sprintf('bulletin_%d_%s.pdf', $bulletin->getId(), date('Ymd_His'));
        $relativePath = 'uploads/bulletins/' . $filename;
        $absolutePath = $this->projectDir . '/public/' . $relativePath;

        $this->ensureDirectory(dirname($absolutePath));
        $this->renderPdf($html, $absolutePath);

        return $relativePath;
    }

    /**
     * Génère le PDF d'une certification et retourne le chemin relatif
     */
    public function generateCertificationPdf(Certification $certification, string $baseUrl): string
    {
        // Génération du QR code
        $verifyUrl = $baseUrl . '/verify/' . $certification->getVerificationCode();
        $qrBase64 = $this->qrCodeService->generateBase64($verifyUrl);

        // Assets
        $logos = $this->signatureAssetRepo->findByType('logo');
        $signatures = $this->signatureAssetRepo->findByType('signature');
        $cachets = $this->signatureAssetRepo->findByType('cachet');

        $html = $this->twig->render('pdf/certification.html.twig', [
            'certification' => $certification,
            'qrBase64' => $qrBase64,
            'verifyUrl' => $verifyUrl,
            'logo' => $logos[0] ?? null,
            'signature' => $signatures[0] ?? null,
            'cachet' => $cachets[0] ?? null,
            'projectDir' => $this->projectDir,
        ]);

        $filename = sprintf('cert_%d_%s.pdf', $certification->getId(), date('Ymd_His'));
        $relativePath = 'uploads/certifications/' . $filename;
        $absolutePath = $this->projectDir . '/public/' . $relativePath;

        $this->ensureDirectory(dirname($absolutePath));
        $this->renderPdf($html, $absolutePath);

        return $relativePath;
    }

    private function renderPdf(string $html, string $outputPath): void
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', $this->projectDir . '/public');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($outputPath, $dompdf->output());
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
