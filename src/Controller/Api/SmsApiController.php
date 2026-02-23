<?php

namespace App\Controller\Api;

use App\Entity\Bulletin;
use App\Entity\Certification;
use App\Repository\BulletinRepository;
use App\Repository\CertificationRepository;
use App\Repository\UserRepository;
use App\Service\SmsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/sms')]
class SmsApiController extends AbstractController
{
    public function __construct(
        private SmsService $smsService,
        private BulletinRepository $bulletinRepository,
        private CertificationRepository $certificationRepository,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Vérifie si le service SMS est configuré
     * GET /api/v1/sms/health
     */
    #[Route('/health', name: 'api_sms_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'configured' => $this->smsService->isConfigured(),
            'provider' => 'Twilio',
            'features' => [
                'bulletin_notification' => true,
                'certification_notification' => true,
                'custom_message' => true,
            ],
        ]);
    }

    /**
     * Envoie une notification SMS pour un bulletin
     * POST /api/v1/sms/notify/bulletin/{id}
     */
    #[Route('/notify/bulletin/{id}', name: 'api_sms_notify_bulletin', methods: ['POST'])]
    public function notifyBulletin(int $id): JsonResponse
    {
        $bulletin = $this->bulletinRepository->find($id);
        
        if (!$bulletin) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Bulletin non trouvé'
            ], 404);
        }

        $result = $this->smsService->notifyBulletinReady($bulletin);

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    /**
     * Envoie une notification SMS pour une certification
     * POST /api/v1/sms/notify/certification/{id}
     */
    #[Route('/notify/certification/{id}', name: 'api_sms_notify_certification', methods: ['POST'])]
    public function notifyCertification(int $id): JsonResponse
    {
        $certification = $this->certificationRepository->find($id);
        
        if (!$certification) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Certification non trouvée'
            ], 404);
        }

        $result = $this->smsService->notifyCertificationReady($certification);

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    /**
     * Envoie un SMS personnalisé à un étudiant
     * POST /api/v1/sms/send
     * Body: { "student_id": 1, "message": "Votre message" }
     */
    #[Route('/send', name: 'api_sms_send', methods: ['POST'])]
    public function sendCustom(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['student_id']) || empty($data['message'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'student_id et message sont requis'
            ], 400);
        }

        $student = $this->userRepository->find($data['student_id']);
        
        if (!$student) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Étudiant non trouvé'
            ], 404);
        }

        $phone = $student->getPhone();
        if (empty($phone)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Numéro de téléphone non renseigné pour cet étudiant'
            ], 400);
        }

        $result = $this->smsService->sendSms($phone, $data['message']);

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    /**
     * Envoie des notifications en masse pour tous les bulletins publiés non notifiés
     * POST /api/v1/sms/bulk/bulletins
     * Body: { "bulletin_ids": [1, 2, 3] } (optionnel, sinon tous les publiés)
     */
    #[Route('/bulk/bulletins', name: 'api_sms_bulk_bulletins', methods: ['POST'])]
    public function bulkNotifyBulletins(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['bulletin_ids'] ?? null;

        if ($ids) {
            $bulletins = $this->bulletinRepository->findBy(['id' => $ids, 'status' => 'Publié']);
        } else {
            $bulletins = $this->bulletinRepository->findBy(['status' => 'Publié']);
        }

        $results = [
            'total' => count($bulletins),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($bulletins as $bulletin) {
            $result = $this->smsService->notifyBulletinReady($bulletin);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'bulletin_id' => $bulletin->getId(),
                    'student' => $bulletin->getStudent()?->getName(),
                    'error' => $result['error'],
                ];
            }
        }

        return new JsonResponse([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Envoie des notifications en masse pour les certifications
     * POST /api/v1/sms/bulk/certifications
     */
    #[Route('/bulk/certifications', name: 'api_sms_bulk_certifications', methods: ['POST'])]
    public function bulkNotifyCertifications(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['certification_ids'] ?? null;

        if ($ids) {
            $certifications = $this->certificationRepository->findBy(['id' => $ids, 'status' => 'ACTIVE']);
        } else {
            $certifications = $this->certificationRepository->findBy(['status' => 'ACTIVE']);
        }

        $results = [
            'total' => count($certifications),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($certifications as $certification) {
            $result = $this->smsService->notifyCertificationReady($certification);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'certification_id' => $certification->getId(),
                    'student' => $certification->getStudent()?->getName(),
                    'error' => $result['error'],
                ];
            }
        }

        return new JsonResponse([
            'success' => true,
            'results' => $results,
        ]);
    }
}
