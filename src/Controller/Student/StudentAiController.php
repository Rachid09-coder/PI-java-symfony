<?php

namespace App\Controller\Student;

use App\Entity\User;
use App\Service\AiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/student/ai')]
#[IsGranted('ROLE_USER')]
class StudentAiController extends AbstractController
{
    public function __construct(
        private AiService $aiService
    ) {}

    #[Route('/', name: 'student_ai_index')]
    public function index(): Response
    {
        $apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        $isConfigured = !empty($apiKey) && $apiKey !== 'your_groq_api_key_here';
        
        return $this->render('student/ai/index.html.twig', [
            'isConfigured' => $isConfigured,
        ]);
    }

    #[Route('/my-analysis', name: 'student_ai_my_analysis', methods: ['GET'])]
    public function myAnalysis(): JsonResponse
    {
        $student = $this->getUser();
        if (!$student instanceof User) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        if (empty($apiKey) || $apiKey === 'your_groq_api_key_here') {
            return $this->json([
                'success' => false,
                'error' => 'Le service AI n\'est pas configuré'
            ], 503);
        }

        try {
            $analysis = $this->aiService->analyzeStudentPerformance($student);
            
            return $this->json([
                'success' => true,
                'student' => [
                    'id' => $student->getId(),
                    'name' => $student->getPrenom() . ' ' . $student->getName(),
                ],
                'analysis' => $analysis,
                'generatedAt' => (new \DateTime())->format('c'),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/my-recommendations', name: 'student_ai_my_recommendations', methods: ['GET'])]
    public function myRecommendations(): JsonResponse
    {
        $student = $this->getUser();
        if (!$student instanceof User) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        if (empty($apiKey) || $apiKey === 'your_groq_api_key_here') {
            return $this->json([
                'success' => false,
                'error' => 'Le service AI n\'est pas configuré'
            ], 503);
        }

        try {
            $recommendations = $this->aiService->generateRecommendations($student);
            
            return $this->json([
                'success' => true,
                'recommendations' => $recommendations,
                'generatedAt' => (new \DateTime())->format('c'),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/chat', name: 'student_ai_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $student = $this->getUser();
        if (!$student instanceof User) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        if (empty($apiKey) || $apiKey === 'your_groq_api_key_here') {
            return $this->json([
                'success' => false,
                'error' => 'Le service AI n\'est pas configuré'
            ], 503);
        }

        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';
        $history = $data['history'] ?? [];

        if (empty($message)) {
            return $this->json([
                'success' => false,
                'error' => 'Message vide'
            ], 400);
        }

        try {
            $result = $this->aiService->chat($student, $message, $history);
            
            return $this->json([
                'success' => true,
                'response' => $result['response'],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}
