<?php

namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    /**
     * Génère un QR code en data URI (PNG si GD disponible, sinon SVG) pour une URL de vérification.
     * Retourne une chaîne prête pour src="..." (data:image/png;base64,... ou data:image/svg+xml;base64,...).
     */
    public function generateBase64(string $url): string
    {
        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 10
        );

        if (extension_loaded('gd')) {
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            return 'data:image/png;base64,' . base64_encode($result->getString());
        }

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        return 'data:image/svg+xml;base64,' . base64_encode($result->getString());
    }

    /**
     * Génère un QR code et le sauvegarde dans un fichier (PNG si GD disponible, sinon SVG).
     */
    public function generateToFile(string $url, string $filePath): void
    {
        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 10
        );

        if (extension_loaded('gd')) {
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            $result->saveToFile($filePath);
            return;
        }

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        if (!str_ends_with(strtolower($filePath), '.svg')) {
            $filePath = preg_replace('/\.[a-z]+$/i', '.svg', $filePath) ?: $filePath . '.svg';
        }
        $result->saveToFile($filePath);
    }
}
