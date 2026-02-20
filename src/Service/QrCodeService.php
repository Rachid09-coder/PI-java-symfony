<?php

namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /**
     * Génère un QR code en base64 (PNG) pour une URL de vérification
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

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return base64_encode($result->getString());
    }

    /**
     * Génère un QR code et le sauvegarde dans un fichier
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

        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $result->saveToFile($filePath);
    }
}
