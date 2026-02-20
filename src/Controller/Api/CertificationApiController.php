<?php

namespace App\Controller\Api;

use App\Entity\Certification;
use App\Repository\CertificationRepository;
use App\Service\HmacService;
use App\Service\PdfGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/certifications')]
class CertificationApiController extends AbstractController
{
    public function __construct(
        private CertificationRepository $certificationRepository,
        private HmacService $hmacService,
        private PdfGeneratorService $pdfService,
    ) {
    }

    /**
     * Liste toutes les certifications avec filtres
     * GET /api/v1/certifications?student_id=X&type=NOTES&status=ACTIVE
     */
    #[Route('', name: 'api_certification_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $studentId = $request->query->get('student_id');
        $type = $request->query->get('type');
        $status = $request->query->get('status');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        $qb = $this->certificationRepository->createQueryBuilder('c')
            ->leftJoin('c.student', 's')
            ->addSelect('s')
            ->leftJoin('c.bulletin', 'b')
            ->addSelect('b');

        if ($studentId) {
            $qb->andWhere('c.student = :studentId')->setParameter('studentId', $studentId);
        }
        if ($type) {
            $qb->andWhere('c.type = :type')->setParameter('type', strtoupper($type));
        }
        if ($status) {
            $qb->andWhere('c.status = :status')->setParameter('status', strtoupper($status));
        }

        $qb->orderBy('c.issuedAt', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $certifications = $qb->getQuery()->getResult();

        $data = array_map(fn(Certification $c) => $this->serializeCertification($c), $certifications);

        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'count' => count($data),
            ]
        ]);
    }

    /**
     * Récupère une certification par ID
     * GET /api/v1/certifications/{id}
     */
    #[Route('/{id}', name: 'api_certification_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $certification = $this->certificationRepository->find($id);

        if (!$certification) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Certification non trouvée',
                'code' => 'NOT_FOUND'
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeCertification($certification, true)
        ]);
    }

    /**
     * Récupère les certifications d'un étudiant
     * GET /api/v1/certifications/student/{studentId}
     */
    #[Route('/student/{studentId}', name: 'api_certification_by_student', methods: ['GET'])]
    public function byStudent(int $studentId): JsonResponse
    {
        $certifications = $this->certificationRepository->findBy(
            ['student' => $studentId],
            ['issuedAt' => 'DESC']
        );

        return new JsonResponse([
            'success' => true,
            'data' => array_map(fn(Certification $c) => $this->serializeCertification($c), $certifications),
            'count' => count($certifications)
        ]);
    }

    /**
     * Vérifie une certification via code de vérification
     * GET /api/v1/certifications/verify/{verificationCode}
     */
    #[Route('/verify/{verificationCode}', name: 'api_certification_verify', methods: ['GET'])]
    public function verify(string $verificationCode): JsonResponse
    {
        $certification = $this->certificationRepository->findOneBy(['verificationCode' => $verificationCode]);

        if (!$certification) {
            return new JsonResponse([
                'success' => false,
                'verified' => false,
                'error' => 'Code de vérification invalide',
                'code' => 'INVALID_CODE'
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier le hash HMAC
        $isIntegrityValid = $this->hmacService->verifyCertification($certification);
        $isActive = $certification->getStatus() === 'ACTIVE';
        $isNotExpired = $certification->getValidUntil() === null || 
                        $certification->getValidUntil() > new \DateTimeImmutable();

        return new JsonResponse([
            'success' => true,
            'verified' => $isIntegrityValid && $isActive && $isNotExpired,
            'details' => [
                'integrityValid' => $isIntegrityValid,
                'isActive' => $isActive,
                'isNotExpired' => $isNotExpired,
                'isRevoked' => $certification->isRevoked(),
            ],
            'data' => [
                'id' => $certification->getId(),
                'uniqueNumber' => $certification->getUniqueNumber(),
                'type' => $certification->getType(),
                'typeLabel' => $certification->getTypeLabel(),
                'student' => $certification->getStudent()->getPrenom() . ' ' . $certification->getStudent()->getName(),
                'issuedAt' => $certification->getIssuedAt()->format('d/m/Y'),
                'validUntil' => $certification->getValidUntil()?->format('d/m/Y'),
                'status' => $certification->getStatus(),
            ]
        ]);
    }

    /**
     * Vérifie une certification par numéro unique
     * GET /api/v1/certifications/verify-number/{uniqueNumber}
     */
    #[Route('/verify-number/{uniqueNumber}', name: 'api_certification_verify_number', methods: ['GET'])]
    public function verifyByNumber(string $uniqueNumber): JsonResponse
    {
        $certification = $this->certificationRepository->findOneBy(['uniqueNumber' => $uniqueNumber]);

        if (!$certification) {
            return new JsonResponse([
                'success' => false,
                'verified' => false,
                'error' => 'Numéro de certification invalide',
                'code' => 'INVALID_NUMBER'
            ], Response::HTTP_NOT_FOUND);
        }

        $isIntegrityValid = $this->hmacService->verifyCertification($certification);
        $isActive = $certification->getStatus() === 'ACTIVE';

        return new JsonResponse([
            'success' => true,
            'verified' => $isIntegrityValid && $isActive,
            'data' => $this->serializeCertification($certification)
        ]);
    }

    /**
     * Statistiques des certifications d'un étudiant
     * GET /api/v1/certifications/student/{studentId}/stats
     */
    #[Route('/student/{studentId}/stats', name: 'api_certification_student_stats', methods: ['GET'])]
    public function studentStats(int $studentId): JsonResponse
    {
        $certifications = $this->certificationRepository->findBy(['student' => $studentId]);

        if (empty($certifications)) {
            return new JsonResponse([
                'success' => true,
                'data' => null,
                'message' => 'Aucune certification trouvée pour cet étudiant'
            ]);
        }

        $byType = [];
        $byStatus = [];
        $activeCount = 0;
        $revokedCount = 0;

        foreach ($certifications as $cert) {
            $type = $cert->getType();
            $status = $cert->getStatus();
            
            $byType[$type] = ($byType[$type] ?? 0) + 1;
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
            
            if ($status === 'ACTIVE') $activeCount++;
            if ($cert->isRevoked()) $revokedCount++;
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'totalCertifications' => count($certifications),
                'activeCertifications' => $activeCount,
                'revokedCertifications' => $revokedCount,
                'byType' => $byType,
                'byStatus' => $byStatus,
            ]
        ]);
    }

    /**
     * Génère le PDF d'une certification (action)
     * POST /api/v1/certifications/{id}/generate-pdf
     */
    #[Route('/{id}/generate-pdf', name: 'api_certification_generate_pdf', methods: ['POST'])]
    public function generatePdf(int $id): JsonResponse
    {
        $certification = $this->certificationRepository->find($id);

        if (!$certification) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Certification non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $pdfPath = $this->pdfService->generateCertificationPdf($certification);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'PDF généré avec succès',
                'data' => [
                    'pdfPath' => $pdfPath,
                    'downloadUrl' => '/uploads/certifications/' . basename($pdfPath),
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Types de certifications disponibles
     * GET /api/v1/certifications/types
     */
    #[Route('/types', name: 'api_certification_types', methods: ['GET'])]
    public function types(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => [
                ['value' => 'SCOLARITE', 'label' => 'Attestation de scolarité'],
                ['value' => 'REUSSITE', 'label' => 'Certificat de réussite'],
                ['value' => 'NOTES', 'label' => 'Relevé de notes'],
                ['value' => 'DIPLOME', 'label' => 'Diplôme interne'],
                ['value' => 'STAGE', 'label' => 'Attestation de stage'],
                ['value' => 'PRESENCE', 'label' => 'Attestation de présence'],
            ]
        ]);
    }

    /**
     * Sérialise une certification en tableau
     */
    private function serializeCertification(Certification $certification, bool $detailed = false): array
    {
        $data = [
            'id' => $certification->getId(),
            'uniqueNumber' => $certification->getUniqueNumber(),
            'type' => $certification->getType(),
            'typeLabel' => $certification->getTypeLabel(),
            'student' => [
                'id' => $certification->getStudent()->getId(),
                'name' => $certification->getStudent()->getName(),
                'prenom' => $certification->getStudent()->getPrenom(),
                'email' => $certification->getStudent()->getEmail(),
            ],
            'status' => $certification->getStatus(),
            'issuedAt' => $certification->getIssuedAt()->format('c'),
            'validUntil' => $certification->getValidUntil()?->format('c'),
            'verificationCode' => $certification->getVerificationCode(),
            'isRevoked' => $certification->isRevoked(),
        ];

        if ($detailed) {
            $data['bulletin'] = null;
            if ($certification->getBulletin()) {
                $bulletin = $certification->getBulletin();
                $data['bulletin'] = [
                    'id' => $bulletin->getId(),
                    'academicYear' => $bulletin->getAcademicYear(),
                    'semester' => $bulletin->getSemester(),
                    'average' => $bulletin->getAverage(),
                    'mention' => $bulletin->getMention(),
                ];
            }
            $data['pdfPath'] = $certification->getPdfPath();
            $data['hmacHash'] = $certification->getHmacHash();
            $data['revokedAt'] = $certification->getRevokedAt()?->format('c');
            $data['revocationReason'] = $certification->getRevocationReason();
        }

        return $data;
    }
}
