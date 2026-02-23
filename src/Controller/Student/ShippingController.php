<?php

declare(strict_types=1);

namespace App\Controller\Student;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\ShippingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Lets students track their shipments.
 *
 * Routes:
 *   GET|POST /student/shipping/track               – free tracking by tracking number (manual)
 *   GET      /student/shipping/status/{trackerId}  – direct EasyPost tracker lookup
 *   GET      /student/shipping/order/{orderId}     – track by Order ID (post-checkout)
 */
#[Route('/student/shipping', name: 'student_shipping_')]
#[IsGranted('ROLE_USER')]
class ShippingController extends AbstractController
{
    public function __construct(
        private readonly ShippingService $shippingService,
        private readonly OrderRepository $orderRepository,
    ) {}

    // ── 1. Manual tracking form ──────────────────────────────────

    #[Route('/track', name: 'track', methods: ['GET', 'POST'])]
    public function track(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        $tracker          = null;
        $error            = null;
        $submittedCode    = null;
        $submittedCarrier = 'auto';

        if ($request->isMethod('POST')) {
            $submittedCode    = trim((string) $request->request->get('tracking_code', ''));
            $submittedCarrier = trim((string) $request->request->get('carrier', 'auto'));

            if ($submittedCode === '') {
                $error = 'Veuillez saisir un numéro de suivi.';
            } else {
                try {
                    $tracker = $this->shippingService->createTracker($submittedCode, $submittedCarrier);
                } catch (\InvalidArgumentException $e) {
                    $error = $e->getMessage();
                } catch (\RuntimeException $e) {
                    $error = 'Impossible de récupérer les informations de suivi : ' . $e->getMessage();
                }
            }
        }

        return $this->render('shipping/track.html.twig', [
            'tracker'           => $tracker,
            'error'             => $error,
            'submitted_code'    => $submittedCode,
            'submitted_carrier' => $submittedCarrier,
        ]);
    }

    // ── 2. Direct EasyPost tracker ID link ──────────────────────

    #[Route('/status/{trackerId}', name: 'status', methods: ['GET'])]
    public function status(string $trackerId): Response
    {
        $tracker = null;
        $error   = null;

        try {
            $tracker = $this->shippingService->getTrackingStatus($trackerId);
        } catch (\InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (\RuntimeException $e) {
            $error = 'Impossible de récupérer le suivi : ' . $e->getMessage();
        }

        return $this->render('shipping/track.html.twig', [
            'tracker'           => $tracker,
            'error'             => $error,
            'submitted_code'    => $tracker['tracking_code'] ?? '',
            'submitted_carrier' => $tracker['carrier'] ?? 'auto',
        ]);
    }

    // ── 3. Order-scoped tracking (post-checkout) ─────────────────

    /**
     * Loads an Order from DB, reads its EasyPost trackerId,
     * fetches live status from EasyPost and renders it.
     * Only the order owner can view this page.
     */
    #[Route('/order/{orderId}', name: 'order', methods: ['GET'])]
    public function orderTracking(int $orderId): Response
    {
        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $order = $this->orderRepository->find($orderId);

        if (!$order || $order->getUser()->getId() !== $user->getId()) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $tracker = null;
        $error   = null;

        if ($order->getTrackerId()) {
            try {
                $tracker = $this->shippingService->getTrackingStatus($order->getTrackerId());
            } catch (\RuntimeException $e) {
                // Show the last known DB status rather than crashing the page
                $error = 'Impossible de contacter EasyPost : ' . $e->getMessage();
            }
        } else {
            $error = 'Aucun numéro de suivi associé à cette commande pour l\'instant.';
        }

        return $this->render('shipping/order_track.html.twig', [
            'order'   => $order,
            'tracker' => $tracker,
            'error'   => $error,
        ]);
    }
}
