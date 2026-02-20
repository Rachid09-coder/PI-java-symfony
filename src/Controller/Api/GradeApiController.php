<?php

namespace App\Controller\Api;

use App\Service\GradeCalculationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/grades')]
class GradeApiController extends AbstractController
{
    public function __construct(
        private GradeCalculationService $gradeService
    ) {}

    /**
     * Récupère les notes d'un étudiant pour une période donnée
     * GET /api/grades/student/{studentId}?academicYear=2025-2026&semester=1
     */
    #[Route('/student/{studentId}', name: 'api_grades_student', methods: ['GET'])]
    public function getStudentGrades(int $studentId, Request $request): JsonResponse
    {
        $academicYear = $request->query->get('academicYear');
        $semester = $request->query->get('semester');

        if (!$academicYear || !$semester) {
            return $this->json([
                'success' => false,
                'error' => 'academicYear et semester sont requis',
            ], 400);
        }

        // Convertir le semestre en entier
        $semesterInt = $this->parseSemester($semester);

        try {
            $data = $this->gradeService->getStudentGradesData($studentId, $academicYear, $semesterInt);
            
            return $this->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifie si des notes existent pour un étudiant/période
     * GET /api/grades/check/{studentId}?academicYear=2025-2026&semester=1
     */
    #[Route('/check/{studentId}', name: 'api_grades_check', methods: ['GET'])]
    public function checkGradesExist(int $studentId, Request $request): JsonResponse
    {
        $academicYear = $request->query->get('academicYear');
        $semester = $request->query->get('semester');

        if (!$academicYear || !$semester) {
            return $this->json([
                'success' => false,
                'error' => 'academicYear et semester sont requis',
            ], 400);
        }

        $semesterInt = $this->parseSemester($semester);
        $data = $this->gradeService->getStudentGradesData($studentId, $academicYear, $semesterInt);
        
        return $this->json([
            'success' => true,
            'hasGrades' => !empty($data['grades']),
            'gradesCount' => count($data['grades']),
            'average' => $data['average'],
            'mention' => $data['mention'],
        ]);
    }

    /**
     * Convertit le semestre de string en int
     */
    private function parseSemester(string $semester): int
    {
        // Accepte "1", "2", "Semestre 1", "Semestre 2", "S1", "S2"
        if (preg_match('/(\d+)/', $semester, $matches)) {
            return (int) $matches[1];
        }
        return 1; // Par défaut semestre 1
    }
}
