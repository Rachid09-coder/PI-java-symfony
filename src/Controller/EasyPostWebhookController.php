<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Receives real-time tracker updates pushed by EasyPost.
 *
 * Register this URL in your EasyPost dashboard:
 *   https://www.easypost.com/account/webhooks
 *
 * In production, verify the X-Hmac-Signature header before processing
 * to ensure the payload truly comes from EasyPost.
 */
class EasyPostWebhookController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository      $orderRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/webhook/easypost', name: 'webhook_easypost', methods: ['POST'])]
    public function handle(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!$payload || !isset($payload['description'], $payload['result'])) {
            return $this->json(['error' => 'Invalid payload'], 400);
        }

        // We only care about tracker updates
        if ($payload['description'] !== 'tracker.updated') {
            return $this->json(['ignored' => true]);
        }

        $result    = $payload['result'];
        $trackerId = $result['id'] ?? null;
        $status    = $result['status'] ?? null;

        if (!$trackerId || !$status) {
            return $this->json(['error' => 'Missing tracker id or status'], 400);
        }

        // Find the matching order and update its shipping status
        $order = $this->orderRepository->findOneByTrackerId($trackerId);

        if ($order) {
            $order
                ->setShippingStatus($status)
                ->setShippingUpdatedAt(new \DateTimeImmutable());

            $this->em->flush();
        }

        return $this->json(['success' => true]);
    }
}
