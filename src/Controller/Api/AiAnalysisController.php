<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\GeminiAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/ai')]
class AiAnalysisController extends AbstractController
{
    public function __construct(
        private GeminiAiService $aiService,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Analyse les performances d'un étudiant
     * GET /api/v1/ai/analyze/student/{studentId}
     */
    #[Route('/analyze/student/{studentId}', name: 'api_ai_analyze_student', methods: ['GET'])]
    public function analyzeStudent(int $studentId): JsonResponse
    {
        $student = $this->userRepository->find($studentId);

        if (!$student) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Étudiant non trouvé',
                'code' => 'STUDENT_NOT_FOUND'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($student->getRole() !== 'etudiant') {
            return new JsonResponse([
                'success' => false,
                'error' => 'L\'utilisateur n\'est pas un étudiant',
                'code' => 'NOT_A_STUDENT'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->aiService->analyzeStudentPerformance($student);

        return new JsonResponse($result, $result['success'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Génère des recommandations pour un étudiant
     * GET /api/v1/ai/recommendations/student/{studentId}
     */
    #[Route('/recommendations/student/{studentId}', name: 'api_ai_recommendations', methods: ['GET'])]
    public function studentRecommendations(int $studentId): JsonResponse
    {
        $student = $this->userRepository->find($studentId);

        if (!$student || $student->getRole() !== 'etudiant') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Étudiant non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $result = $this->aiService->generateRecommendations($student);

        return new JsonResponse($result, $result['success'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Compare plusieurs étudiants
     * POST /api/v1/ai/compare
     * Body: { "student_ids": [1, 2, 3] }
     */
    #[Route('/compare', name: 'api_ai_compare_students', methods: ['POST'])]
    public function compareStudents(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['student_ids']) || !is_array($data['student_ids'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Paramètre student_ids requis (tableau d\'IDs)'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (count($data['student_ids']) < 2) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Au moins 2 étudiants sont requis pour la comparaison'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (count($data['student_ids']) > 10) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Maximum 10 étudiants peuvent être comparés'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->aiService->compareStudents($data['student_ids']);

        return new JsonResponse($result, $result['success'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Analyse les tendances d'une classe
     * GET /api/v1/ai/analyze/class?academic_year=2025/2026&semester=S1
     */
    #[Route('/analyze/class', name: 'api_ai_analyze_class', methods: ['GET'])]
    public function analyzeClass(Request $request): JsonResponse
    {
        $academicYear = $request->query->get('academic_year');
        $semester = $request->query->get('semester');

        if (!$academicYear || !$semester) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Paramètres academic_year et semester requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->aiService->analyzeClassTrends($academicYear, $semester);

        return new JsonResponse($result, $result['success'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Chat interactif avec contexte étudiant
     * POST /api/v1/ai/chat/{studentId}
     * Body: { "message": "...", "history": [...] }
     */
    #[Route('/chat/{studentId}', name: 'api_ai_chat', methods: ['POST'])]
    public function chat(int $studentId, Request $request): JsonResponse
    {
        $student = $this->userRepository->find($studentId);

        if (!$student || $student->getRole() !== 'etudiant') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Étudiant non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['message']) || empty(trim($data['message']))) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Message requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $history = $data['history'] ?? [];
        $result = $this->aiService->chat($student, $data['message'], $history);

        return new JsonResponse($result, $result['success'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Vérifier la configuration AI
     * GET /api/v1/ai/health
     */
    #[Route('/health', name: 'api_ai_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
        $isConfigured = !empty($apiKey) && $apiKey !== 'your_gemini_api_key_here';

        return new JsonResponse([
            'success' => true,
            'status' => $isConfigured ? 'configured' : 'not_configured',
            'provider' => 'Google Gemini',
            'model' => 'gemini-1.5-flash',
            'features' => [
                'student_analysis' => true,
                'recommendations' => true,
                'student_comparison' => true,
                'class_trends' => true,
                'interactive_chat' => true,
            ],
            'endpoints' => [
                'GET /api/v1/ai/analyze/student/{id}' => 'Analyse individuelle',
                'GET /api/v1/ai/recommendations/student/{id}' => 'Recommandations',
                'POST /api/v1/ai/compare' => 'Comparaison étudiants',
                'GET /api/v1/ai/analyze/class' => 'Tendances de classe',
                'POST /api/v1/ai/chat/{id}' => 'Chat interactif',
            ]
        ]);
    }
}
