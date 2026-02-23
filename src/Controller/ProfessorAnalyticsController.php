<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/professor')]
#[IsGranted('ROLE_PROFESSEUR')]
class ProfessorAnalyticsController extends AbstractController
{
    public function __construct(
        private readonly GeminiService $geminiService,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
    ) {}

    #[Route('/ai-analytics', name: 'professor_ai_analytics', methods: ['GET'])]
    public function aiAnalytics(): JsonResponse
    {
        try {
            // 1. Setup Time Periods (Current 30 days vs Previous 30 days)
            $now = new \DateTimeImmutable();
            $last30DaysStart = $now->sub(new \DateInterval('P30D'));
            $prev30DaysStart = $last30DaysStart->sub(new \DateInterval('P30D'));

            // 2. Data Collection
            $currentOrders = $this->orderRepository->findByDateRange($last30DaysStart, $now);
            $previousOrders = $this->orderRepository->findByDateRange($prev30DaysStart, $last30DaysStart);
            $products = $this->productRepository->findAll();

            // 3. Sales Evolution Calculation (Daily for last 30 days)
            $dailySales = [];
            $tempDate = $last30DaysStart;
            for ($i = 0; $i <= 30; $i++) {
                $dailySales[$tempDate->format('Y-m-d')] = 0;
                $tempDate = $tempDate->add(new \DateInterval('P1D'));
            }

            $currentGain = 0;
            $productVolume = [];
            foreach ($currentOrders as $order) {
                $dateKey = $order->getCreatedAt()->format('Y-m-d');
                $amount = (float) $order->getTotalAmount();
                if (isset($dailySales[$dateKey])) {
                    $dailySales[$dateKey] += $amount;
                }
                $currentGain += $amount;
                
                foreach ($order->getItems() as $item) {
                    $pid = $item['id'] ?? null;
                    if ($pid) {
                        $productVolume[$pid] = ($productVolume[$pid] ?? 0) + 1;
                    }
                }
            }

            $previousGain = 0;
            foreach ($previousOrders as $order) {
                $previousGain += (float) $order->getTotalAmount();
            }

            // 4. Performance Detection
            $gainDiffPercent = $previousGain > 0 ? (($currentGain - $previousGain) / $previousGain) * 100 : 0;
            $isPerformanceDrop = $gainDiffPercent < -10;

            // 5. Product Scoring & Stagnation Detection
            $productMetrics = [];
            foreach ($products as $product) {
                $salesCount = $productVolume[$product->getId()] ?? 0;
                
                // Heuristic score: higher is better (mix of sales and stock health)
                $score = ($salesCount * 10) + ($product->getStock() > 0 ? 5 : -10);
                $status = $score > 50 ? 'Top' : ($score > 10 ? 'Moyen' : 'Faible');

                $productMetrics[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'stock' => $product->getStock(),
                    'sales_30d' => $salesCount,
                    'score_label' => $status,
                    'is_stagnant' => ($salesCount === 0 && count($currentOrders) > 5)
                ];
            }

            // 6. Gemini Prompt with Rich Context
            $context = [
                'period' => 'Derniers 30 jours vs 30 jours précédents',
                'current_gain' => $currentGain,
                'previous_gain' => $previousGain,
                'gain_diff_percent' => round($gainDiffPercent, 1),
                'total_orders' => count($currentOrders),
                'daily_sales_trend' => array_values($dailySales),
                'product_performance' => $productMetrics,
            ];

            $prompt = "Tu es un consultant expert en stratégie e-commerce.\n";
            $prompt .= "Données actuelles :\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
            $prompt .= "Analyse ces données et retourne un JSON pur avec EXACTEMENT cette structure :\n";
            $prompt .= "- summary: (string) Résumé de la situation.\n";
            $prompt .= "- performance_alert: (string|null) Message si baisse > 10%, sinon null.\n";
            $prompt .= "- price_optimization: (Array d'objets) [{product_name, current_price, suggestion, justification}]. Si rien, retourne [].\n";
            $prompt .= "- stagnant_products: (Array d'objets) [{product_name, recommendation}]. Si rien, retourne [].\n";
            $prompt .= "- forecast: {gain_30d_estimate, stock_out_risks (Array de strings), profit_estimate_total}.\n";
            $prompt .= "- strategy: (Array de strings) 3 conseils prioritaires.\n";
            $prompt .= "Répond uniquement en JSON pur sans markdown bloc codes.";

            $aiResponse = $this->geminiService->generateAnalysis($prompt);
            
            if ($aiResponse === 'RATE_LIMIT_EXCEEDED') {
                $analysis = [
                    'summary' => 'Données calculées localement (IA indisponible).',
                    'performance_alert' => $isPerformanceDrop ? "Alerte : baisse de " . abs(round($gainDiffPercent, 1)) . "% par rapport à la période précédente." : null,
                    'price_optimization' => [],
                    'stagnant_products' => array_map(fn($p) => ['product_name' => $p['name'], 'recommendation' => 'Analyser la performance'], array_filter($productMetrics, fn($p) => $p['is_stagnant'])),
                    'forecast' => ['gain_30d_estimate' => $currentGain, 'stock_out_risks' => [], 'profit_estimate_total' => $currentGain * 1.2],
                    'strategy' => ["Vérifier les produits à score Faible", "Optimiser le stock", "Lancer une campagne marketing"]
                ];
            } else {
                $cleanJson = preg_replace('/^```json\s*|\s*```$/i', '', trim($aiResponse));
                $analysis = json_decode($cleanJson, true) ?? [];
            }

            return new JsonResponse([
                'success' => true,
                'is_limited' => ($aiResponse === 'RATE_LIMIT_EXCEEDED'),
                'data' => $analysis,
                'chart_data' => [
                    'labels' => array_keys($dailySales),
                    'values' => array_values($dailySales)
                ],
                'product_scores' => array_map(fn($p) => ['id' => $p['id'], 'label' => $p['score_label']], $productMetrics),
                'stats' => [
                    'totalGain' => number_format($currentGain, 2) . ' €',
                    'orderCount' => count($currentOrders),
                    'potentialGain' => number_format($currentGain * 1.1, 2) . ' €', // Simple estimation
                ]
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
