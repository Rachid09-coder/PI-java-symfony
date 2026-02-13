<?php

namespace App\Controller;

use App\Entity\Bulletin;
use App\Entity\Certification;
use App\Repository\BulletinRepository;
use App\Repository\CertificationRepository;
use App\Service\HmacService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VerificationController extends AbstractController
{
    public function __construct(
        private BulletinRepository $bulletinRepo,
        private CertificationRepository $certificationRepo,
        private HmacService $hmacService,
    ) {
    }

    /**
     * Page publique de vérification — accessible sans authentification
     */
    #[Route('/verify/{code}', name: 'public_verify', methods: ['GET'])]
    public function verify(string $code): Response
    {
        // Chercher d'abord dans les bulletins
        $bulletin = $this->bulletinRepo->findOneBy(['verificationCode' => $code]);
        if ($bulletin) {
            return $this->renderBulletinVerification($bulletin);
        }

        // Chercher dans les certifications
        $certification = $this->certificationRepo->findOneBy(['verificationCode' => $code]);
        if ($certification) {
            return $this->renderCertificationVerification($certification);
        }

        // Code introuvable
        return $this->render('verification/verify.html.twig', [
            'found' => false,
            'code' => $code,
        ]);
    }

    private function renderBulletinVerification(Bulletin $bulletin): Response
    {
        $student = $bulletin->getStudent();
        $name = $student ? $this->maskName($student->getName(), $student->getPrenom()) : 'N/A';
        $hmacValid = $this->hmacService->verifyBulletin($bulletin);

        return $this->render('verification/verify.html.twig', [
            'found' => true,
            'type' => 'Bulletin de notes',
            'studentName' => $name,
            'academicYear' => $bulletin->getAcademicYear(),
            'semester' => $bulletin->getSemester(),
            'status' => $bulletin->isRevoked() ? 'Révoqué' : 'Valide',
            'isRevoked' => $bulletin->isRevoked(),
            'revocationReason' => $bulletin->getRevocationReason(),
            'publishedAt' => $bulletin->getPublishedAt(),
            'hmacValid' => $hmacValid,
            'code' => $bulletin->getVerificationCode(),
        ]);
    }

    private function renderCertificationVerification(Certification $certification): Response
    {
        $student = $certification->getStudent();
        $name = $student ? $this->maskName($student->getName(), $student->getPrenom()) : 'N/A';
        $hmacValid = $this->hmacService->verifyCertification($certification);

        return $this->render('verification/verify.html.twig', [
            'found' => true,
            'type' => $certification->getTypeLabel(),
            'studentName' => $name,
            'uniqueNumber' => $certification->getUniqueNumber(),
            'academicYear' => $certification->getBulletin()?->getAcademicYear(),
            'semester' => $certification->getBulletin()?->getSemester(),
            'status' => $certification->isRevoked() ? 'Révoqué' : 'Valide',
            'isRevoked' => $certification->isRevoked(),
            'revocationReason' => $certification->getRevocationReason(),
            'issuedAt' => $certification->getIssuedAt(),
            'hmacValid' => $hmacValid,
            'code' => $certification->getVerificationCode(),
        ]);
    }

    /**
     * Masque partiellement le nom de l'étudiant pour la page publique
     */
    private function maskName(string $nom, string $prenom): string
    {
        $maskedNom = mb_substr($nom, 0, 2) . str_repeat('*', max(0, mb_strlen($nom) - 2));
        $maskedPrenom = mb_substr($prenom, 0, 1) . str_repeat('*', max(0, mb_strlen($prenom) - 1));
        return $maskedPrenom . ' ' . $maskedNom;
    }
}
