<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{
    #[Route('/uploads/{path}', name: 'serve_upload', requirements: ['path' => '.+'], methods: ['GET'])]
    public function serve(string $path, Request $request): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadsDir = $projectDir . \DIRECTORY_SEPARATOR . 'public' . \DIRECTORY_SEPARATOR . 'uploads';
        $requestedFile = $uploadsDir . \DIRECTORY_SEPARATOR . str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $path);

        $realUploads = realpath($uploadsDir);
        $realFile = realpath($requestedFile);

        if ($realUploads === false || $realFile === false) {
            throw new NotFoundHttpException('Fichier introuvable.');
        }

        // Empêcher l'accès en dehors du dossier uploads (path traversal)
        if (str_starts_with($realFile, $realUploads) === false) {
            throw new NotFoundHttpException('Fichier introuvable.');
        }

        if (!is_file($realFile)) {
            throw new NotFoundHttpException('Fichier introuvable.');
        }

        $response = new BinaryFileResponse($realFile);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
        ];
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        $response->headers->set('Content-Type', $mime);

        return $response;
    }
}
