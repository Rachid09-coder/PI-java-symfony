<?php

declare(strict_types=1);

namespace App\Controller\Student;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\ShippingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Handles the student checkout flow.
 *
 * The cart lives in localStorage. When the student clicks "Passer au paiement"
 * the JS fetches POST /student/checkout with the cart JSON.
 * This controller saves the Order as paid and fires the EasyPost tracker.
 */
#[Route('/student', name: 'student_')]
#[IsGranted('ROLE_USER')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ShippingService $shippingService,
        private readonly OrderRepository $orderRepository,
    ) {}

    /**
     * Accepts a JSON body:
     *   { "items": [{id, name, price}, ...], "total": 99.99 }
     *
     * Returns:
     *   { "success": true, "orderId": 42 }
     */
    #[Route('/checkout', name: 'checkout', methods: ['POST'])]
    public function checkout(Request $request): JsonResponse
    {
        // 1. Parse the cart JSON sent from the browser
        $payload = json_decode($request->getContent(), true);

        $items = $payload['items'] ?? [];
        $total = (float) ($payload['total'] ?? 0.0);

        if (empty($items)) {
            return $this->json(['success' => false, 'error' => 'Panier vide.'], 400);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 2. Persist the Order as paid
        $order = (new Order())
            ->setUser($user)
            ->setItems($items)
            ->setTotalAmount(number_format($total, 2, '.', ''))
            ->setStatus(Order::STATUS_PAID);

        $this->em->persist($order);
        $this->em->flush(); // gives the Order its DB id

        // 3. Attach an EasyPost tracker.
        //    Passing 'null' lets the ShippingService automatically decide 
        //    whether to use a Test (EZ...) or Production (USPS-style) number 
        //    based on your EASYPOST_API_KEY prefix.
        $this->shippingService->attachTrackerToOrder($order, null);
        $this->em->flush(); // persist all shipping metadata (tracker ID, public URL, etc.)

        return $this->json([
            'success' => true,
            'orderId' => $order->getId(),
        ]);
    }

    /**
     * Displays the order confirmation / success page.
     * The browser is redirected here by the JS checkout handler.
     */
    #[Route('/order/{orderId}/success', name: 'order_success', methods: ['GET'])]
    public function success(int $orderId): Response
    {
        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $order = $this->orderRepository->find($orderId);

        if (!$order || $order->getUser()->getId() !== $user->getId()) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('order/success.html.twig', [
            'order' => $order,
        ]);
    }
}

