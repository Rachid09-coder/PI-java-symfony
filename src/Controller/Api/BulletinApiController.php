<?php

namespace App\Controller\Api;

use App\Entity\Bulletin;
use App\Repository\BulletinRepository;
use App\Service\HmacService;
use App\Service\PdfGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/bulletins')]
class BulletinApiController extends AbstractController
{
    public function __construct(
        private BulletinRepository $bulletinRepository,
        private HmacService $hmacService,
        private PdfGeneratorService $pdfService,
    ) {
    }

    /**
     * Liste tous les bulletins avec filtres optionnels
     * GET /api/v1/bulletins?student_id=X&academic_year=2025/2026&semester=S1
     */
    #[Route('', name: 'api_bulletin_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $studentId = $request->query->get('student_id');
        $academicYear = $request->query->get('academic_year');
        $semester = $request->query->get('semester');
        $status = $request->query->get('status');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        $qb = $this->bulletinRepository->createQueryBuilder('b')
            ->leftJoin('b.student', 's')
            ->addSelect('s')
            ->leftJoin('b.reportCardLines', 'r')
            ->addSelect('r');

        if ($studentId) {
            $qb->andWhere('b.student = :studentId')->setParameter('studentId', $studentId);
        }
        if ($academicYear) {
            $qb->andWhere('b.academicYear = :year')->setParameter('year', $academicYear);
        }
        if ($semester) {
            $semesterMap = ['S1' => 'Semestre 1', 'S2' => 'Semestre 2', 'ANNUEL' => 'Annuel'];
            $qb->andWhere('b.semester = :semester')
               ->setParameter('semester', $semesterMap[$semester] ?? $semester);
        }
        if ($status) {
            $qb->andWhere('b.status = :status')->setParameter('status', $status);
        }

        $qb->orderBy('b.createdAt', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $bulletins = $qb->getQuery()->getResult();

        $data = array_map(fn(Bulletin $b) => $this->serializeBulletin($b), $bulletins);

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
     * Récupère un bulletin par ID
     * GET /api/v1/bulletins/{id}
     */
    #[Route('/{id}', name: 'api_bulletin_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $bulletin = $this->bulletinRepository->find($id);

        if (!$bulletin) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Bulletin non trouvé',
                'code' => 'NOT_FOUND'
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeBulletin($bulletin, true)
        ]);
    }

    /**
     * Récupère les bulletins d'un étudiant
     * GET /api/v1/bulletins/student/{studentId}
     */
    #[Route('/student/{studentId}', name: 'api_bulletin_by_student', methods: ['GET'])]
    public function byStudent(int $studentId): JsonResponse
    {
        $bulletins = $this->bulletinRepository->findByStudentId($studentId);

        return new JsonResponse([
            'success' => true,
            'data' => array_map(fn(Bulletin $b) => $this->serializeBulletin($b), $bulletins),
            'count' => count($bulletins)
        ]);
    }

    /**
     * Vérifie l'intégrité d'un bulletin via code de vérification
     * GET /api/v1/bulletins/verify/{verificationCode}
     */
    #[Route('/verify/{verificationCode}', name: 'api_bulletin_verify', methods: ['GET'])]
    public function verify(string $verificationCode): JsonResponse
    {
        $bulletin = $this->bulletinRepository->findOneBy(['verificationCode' => $verificationCode]);

        if (!$bulletin) {
            return new JsonResponse([
                'success' => false,
                'verified' => false,
                'error' => 'Code de vérification invalide'
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier le hash HMAC
        $isValid = $this->hmacService->verifyBulletin($bulletin);

        return new JsonResponse([
            'success' => true,
            'verified' => $isValid,
            'data' => [
                'id' => $bulletin->getId(),
                'student' => $bulletin->getStudent()->getPrenom() . ' ' . $bulletin->getStudent()->getName(),
                'academicYear' => $bulletin->getAcademicYear(),
                'semester' => $bulletin->getSemester(),
                'average' => $bulletin->getAverage(),
                'mention' => $bulletin->getMention(),
                'status' => $bulletin->getStatus(),
                'isRevoked' => $bulletin->getRevokedAt() !== null,
            ]
        ]);
    }

    /**
     * Statistiques des bulletins d'un étudiant
     * GET /api/v1/bulletins/student/{studentId}/stats
     */
    #[Route('/student/{studentId}/stats', name: 'api_bulletin_student_stats', methods: ['GET'])]
    public function studentStats(int $studentId): JsonResponse
    {
        $bulletins = $this->bulletinRepository->findByStudentId($studentId);

        if (empty($bulletins)) {
            return new JsonResponse([
                'success' => true,
                'data' => null,
                'message' => 'Aucun bulletin trouvé pour cet étudiant'
            ]);
        }

        $averages = array_filter(array_map(fn($b) => $b->getAverage(), $bulletins));
        $globalAverage = !empty($averages) ? array_sum($averages) / count($averages) : null;
        
        $mentions = [];
        foreach ($bulletins as $b) {
            $m = $b->getMention();
            $mentions[$m] = ($mentions[$m] ?? 0) + 1;
        }

        $progression = [];
        usort($bulletins, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());
        foreach ($bulletins as $b) {
            $progression[] = [
                'period' => $b->getAcademicYear() . ' - ' . $b->getSemester(),
                'average' => $b->getAverage(),
                'rank' => $b->getClassRank(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'totalBulletins' => count($bulletins),
                'globalAverage' => round($globalAverage, 2),
                'highestAverage' => max($averages),
                'lowestAverage' => min($averages),
                'mentionDistribution' => $mentions,
                'progression' => $progression,
            ]
        ]);
    }

    /**
     * Génère le PDF d'un bulletin (action)
     * POST /api/v1/bulletins/{id}/generate-pdf
     */
    #[Route('/{id}/generate-pdf', name: 'api_bulletin_generate_pdf', methods: ['POST'])]
    public function generatePdf(int $id): JsonResponse
    {
        $bulletin = $this->bulletinRepository->find($id);

        if (!$bulletin) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Bulletin non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $pdfPath = $this->pdfService->generateBulletinPdf($bulletin);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'PDF généré avec succès',
                'data' => [
                    'pdfPath' => $pdfPath,
                    'downloadUrl' => '/uploads/bulletins/' . basename($pdfPath),
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
     * Sérialise un bulletin en tableau
     */
    private function serializeBulletin(Bulletin $bulletin, bool $detailed = false): array
    {
        $data = [
            'id' => $bulletin->getId(),
            'student' => [
                'id' => $bulletin->getStudent()->getId(),
                'name' => $bulletin->getStudent()->getName(),
                'prenom' => $bulletin->getStudent()->getPrenom(),
                'email' => $bulletin->getStudent()->getEmail(),
            ],
            'academicYear' => $bulletin->getAcademicYear(),
            'semester' => $bulletin->getSemester(),
            'average' => $bulletin->getAverage(),
            'mention' => $bulletin->getMention(),
            'classRank' => $bulletin->getClassRank(),
            'status' => $bulletin->getStatus(),
            'verificationCode' => $bulletin->getVerificationCode(),
            'createdAt' => $bulletin->getCreatedAt()?->format('c'),
            'isRevoked' => $bulletin->getRevokedAt() !== null,
        ];

        if ($detailed) {
            $data['grades'] = [];
            foreach ($bulletin->getReportCardLines() as $line) {
                $data['grades'][] = [
                    'moduleName' => $line->getModuleName(),
                    'noteCC' => $line->getNoteCC(),
                    'noteDS' => $line->getNoteDS(),
                    'noteExam' => $line->getNoteExam(),
                    'noteFinal' => $line->getNote(),
                    'coefficient' => $line->getCoefficient(),
                    'teacherComment' => $line->getTeacherComment(),
                ];
            }
            $data['validatedBy'] = $bulletin->getValidatedBy()?->getEmail();
            $data['validatedAt'] = $bulletin->getValidatedAt()?->format('c');
            $data['publishedBy'] = $bulletin->getPublishedBy()?->getEmail();
            $data['publishedAt'] = $bulletin->getPublishedAt()?->format('c');
            $data['pdfPath'] = $bulletin->getPdfPath();
        }

        return $data;
    }
}
