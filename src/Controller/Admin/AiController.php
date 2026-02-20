<?php

namespace App\Controller\Admin;

use App\Repository\BulletinRepository;
use App\Repository\UserRepository;
use App\Service\AiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/ai')]
class AiController extends AbstractController
{
    public function __construct(
        private AiService $aiService,
        private UserRepository $userRepository,
        private BulletinRepository $bulletinRepository,
    ) {
    }

    /**
     * Page principale d'analyse AI
     */
    #[Route('/', name: 'admin_ai_index')]
    public function index(): Response
    {
        // Récupérer les étudiants avec bulletins
        $students = $this->userRepository->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', 'etudiant')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Récupérer les années académiques disponibles
        $years = $this->bulletinRepository->createQueryBuilder('b')
            ->select('DISTINCT b.academicYear')
            ->orderBy('b.academicYear', 'DESC')
            ->getQuery()
            ->getResult();

        $academicYears = array_column($years, 'academicYear');

        // Vérifier la configuration API Groq
        $apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        $isConfigured = !empty($apiKey) && $apiKey !== 'your_groq_api_key_here';

        return $this->render('admin/ai/index.html.twig', [
            'students' => $students,
            'academicYears' => $academicYears,
            'isConfigured' => $isConfigured,
        ]);
    }

    /**
     * Analyse d'un étudiant (AJAX)
     */
    #[Route('/analyze/{studentId}', name: 'admin_ai_analyze', methods: ['GET'])]
    public function analyzeStudent(int $studentId): JsonResponse
    {
        $student = $this->userRepository->find($studentId);

        if (!$student || $student->getRole() !== 'etudiant') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Étudiant non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $result = $this->aiService->analyzeStudentPerformance($student);

        return new JsonResponse($result);
    }

    /**
     * Recommandations pour un étudiant (AJAX)
     */
    #[Route('/recommendations/{studentId}', name: 'admin_ai_recommendations', methods: ['GET'])]
    public function recommendations(int $studentId): JsonResponse
    {
        $student = $this->userRepository->find($studentId);

        if (!$student || $student->getRole() !== 'etudiant') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Étudiant non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $result = $this->aiService->generateRecommendations($student);

        return new JsonResponse($result);
    }

    /**
     * Analyse de classe (AJAX)
     */
    #[Route('/class-analysis', name: 'admin_ai_class_analysis', methods: ['GET'])]
    public function classAnalysis(Request $request): JsonResponse
    {
        $academicYear = $request->query->get('academic_year');
        $semester = $request->query->get('semester');

        if (!$academicYear || !$semester) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Paramètres manquants'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->aiService->analyzeClassTrends($academicYear, $semester);

        return new JsonResponse($result);
    }

    /**
     * Comparaison d'étudiants (AJAX)
     */
    #[Route('/compare', name: 'admin_ai_compare', methods: ['POST'])]
    public function compare(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $studentIds = $data['student_ids'] ?? [];

        if (count($studentIds) < 2) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Sélectionnez au moins 2 étudiants'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->aiService->compareStudents($studentIds);

        return new JsonResponse($result);
    }

    /**
     * Chat avec l'AI (AJAX)
     */
    #[Route('/chat/{studentId}', name: 'admin_ai_chat', methods: ['POST'])]
    public function chat(int $studentId, Request $request): JsonResponse
    {
        $student = $this->userRepository->find($studentId);

        if (!$student) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Étudiant non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';
        $history = $data['history'] ?? [];

        if (empty(trim($message))) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Message vide'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->aiService->chat($student, $message, $history);

        return new JsonResponse($result);
    }
}
