<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\BulletinRepository;
use App\Repository\CertificationRepository;
use OpenAI;

/**
 * Service d'analyse de performance utilisant Groq (API gratuite)
 */
class AiService
{
    private ?\OpenAI\Client $client = null;
    private string $apiKey;
    private string $model = 'llama-3.3-70b-versatile';

    public function __construct(
        private BulletinRepository $bulletinRepository,
        private CertificationRepository $certificationRepository,
        string $groqApiKey = ''
    ) {
        $this->apiKey = $groqApiKey ?: ($_ENV['GROQ_API_KEY'] ?? '');
    }

    private function getClient(): \OpenAI\Client
    {
        if ($this->client === null) {
            if (empty($this->apiKey) || $this->apiKey === 'your_groq_api_key_here') {
                throw new \RuntimeException('Clé API Groq non configurée. Ajoutez GROQ_API_KEY dans votre fichier .env');
            }
            // Groq utilise une API compatible OpenAI
            $this->client = OpenAI::factory()
                ->withApiKey($this->apiKey)
                ->withBaseUri('https://api.groq.com/openai/v1')
                ->make();
        }
        return $this->client;
    }

    private function callAI(string $prompt, string $systemPrompt = ''): string
    {
        $messages = [];
        
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        
        $messages[] = ['role' => 'user', 'content' => $prompt];

        // Retry with exponential backoff for rate limits
        $maxRetries = 3;
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $this->getClient()->chat()->create([
                    'model' => $this->model,
                    'messages' => $messages,
                    'max_tokens' => 1500,
                    'temperature' => 0.7,
                ]);

                return $response->choices[0]->message->content;
            } catch (\Exception $e) {
                $lastException = $e;
                if (str_contains($e->getMessage(), 'rate limit') && $attempt < $maxRetries) {
                    sleep(pow(2, $attempt)); // 2s, 4s, 8s
                    continue;
                }
                throw $e;
            }
        }
        
        throw $lastException;
    }

    public function analyzeStudentPerformance(User $student): array
    {
        $bulletins = $this->bulletinRepository->findByStudentId($student->getId());
        $certifications = $this->certificationRepository->findBy(['student' => $student]);

        if (empty($bulletins)) {
            return [
                'success' => false,
                'error' => 'Aucun bulletin trouvé pour cet étudiant',
                'analysis' => null,
            ];
        }

        $studentData = $this->prepareStudentData($student, $bulletins, $certifications);
        $prompt = $this->buildAnalysisPrompt($studentData);

        try {
            $analysisText = $this->callAI($prompt, 'Tu es un expert en analyse de performances académiques pour EduSmart. Réponds en français.');

            return [
                'success' => true,
                'student' => [
                    'id' => $student->getId(),
                    'name' => $student->getPrenom() . ' ' . $student->getName(),
                    'email' => $student->getEmail(),
                ],
                'dataAnalyzed' => [
                    'bulletinsCount' => count($bulletins),
                    'certificationsCount' => count($certifications),
                ],
                'analysis' => $this->parseAnalysisResponse($analysisText),
                'rawAnalysis' => $analysisText,
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'analyse AI: ' . $e->getMessage(),
                'analysis' => null,
            ];
        }
    }

    public function compareStudents(array $studentIds): array
    {
        $studentsData = [];
        
        foreach ($studentIds as $studentId) {
            $bulletins = $this->bulletinRepository->findByStudentId($studentId);
            if (!empty($bulletins)) {
                $student = $bulletins[0]->getStudent();
                $studentsData[] = $this->prepareStudentData($student, $bulletins, []);
            }
        }

        if (count($studentsData) < 2) {
            return [
                'success' => false,
                'error' => 'Au moins 2 étudiants avec des bulletins sont nécessaires',
            ];
        }

        $prompt = $this->buildComparisonPrompt($studentsData);

        try {
            $response = $this->callAI($prompt, 'Tu es un expert en analyse comparative académique. Réponds en français.');

            return [
                'success' => true,
                'studentsCompared' => count($studentsData),
                'comparison' => $response,
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    public function generateRecommendations(User $student): array
    {
        $bulletins = $this->bulletinRepository->findByStudentId($student->getId());

        if (empty($bulletins)) {
            return [
                'success' => false,
                'error' => 'Aucun bulletin trouvé',
            ];
        }

        $studentData = $this->prepareStudentData($student, $bulletins, []);
        $prompt = $this->buildRecommendationsPrompt($studentData);

        try {
            $response = $this->callAI($prompt, 'Tu es un conseiller pédagogique expert. Réponds en français.');

            return [
                'success' => true,
                'student' => $student->getPrenom() . ' ' . $student->getName(),
                'recommendations' => $response,
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    public function analyzeClassTrends(string $academicYear, string $semester): array
    {
        $semesterMap = ['S1' => 'Semestre 1', 'S2' => 'Semestre 2', 'ANNUEL' => 'Annuel'];
        $semesterValue = $semesterMap[$semester] ?? $semester;

        $bulletins = $this->bulletinRepository->createQueryBuilder('b')
            ->where('b.academicYear = :year')
            ->andWhere('b.semester = :semester')
            ->setParameter('year', $academicYear)
            ->setParameter('semester', $semesterValue)
            ->getQuery()
            ->getResult();

        if (empty($bulletins)) {
            return [
                'success' => false,
                'error' => 'Aucun bulletin trouvé pour cette période',
            ];
        }

        $classData = $this->prepareClassData($bulletins);
        $prompt = $this->buildClassAnalysisPrompt($classData, $academicYear, $semesterValue);

        try {
            $response = $this->callAI($prompt, 'Tu es un expert en analyse de données académiques. Réponds en français.');

            return [
                'success' => true,
                'period' => $academicYear . ' - ' . $semesterValue,
                'studentsAnalyzed' => count($bulletins),
                'analysis' => $response,
                'statistics' => $classData['statistics'],
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    public function chat(User $student, string $userMessage, array $history = []): array
    {
        $bulletins = $this->bulletinRepository->findByStudentId($student->getId());
        $studentData = $this->prepareStudentData($student, $bulletins, []);

        $systemContext = "Tu es un assistant pédagogique pour EduSmart. Tu analyses les performances académiques.\n";
        $systemContext .= "Contexte de l'étudiant:\n" . json_encode($studentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $messages = [
            ['role' => 'system', 'content' => $systemContext],
        ];

        foreach ($history as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = $this->getClient()->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            return [
                'success' => true,
                'response' => $response->choices[0]->message->content,
                'studentContext' => $student->getPrenom() . ' ' . $student->getName(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur chat: ' . $e->getMessage(),
            ];
        }
    }

    private function prepareStudentData(User $student, array $bulletins, array $certifications): array
    {
        $gradesHistory = [];
        $allGrades = [];

        foreach ($bulletins as $bulletin) {
            $periodGrades = [];
            foreach ($bulletin->getReportCardLines() as $line) {
                $periodGrades[] = [
                    'module' => $line->getModuleName(),
                    'noteCC' => $line->getNoteCC(),
                    'noteDS' => $line->getNoteDS(),
                    'noteExam' => $line->getNoteExam(),
                    'noteFinal' => $line->getNote(),
                    'coefficient' => $line->getCoefficient(),
                ];
                $allGrades[] = $line->getNote();
            }

            $gradesHistory[] = [
                'period' => $bulletin->getAcademicYear() . ' - ' . $bulletin->getSemester(),
                'average' => $bulletin->getAverage(),
                'mention' => $bulletin->getMention(),
                'rank' => $bulletin->getClassRank(),
                'status' => $bulletin->getStatus(),
                'grades' => $periodGrades,
            ];
        }

        $averages = array_filter(array_column($gradesHistory, 'average'));
        
        return [
            'student' => [
                'name' => $student->getPrenom() . ' ' . $student->getName(),
                'email' => $student->getEmail(),
            ],
            'statistics' => [
                'totalBulletins' => count($bulletins),
                'totalCertifications' => count($certifications),
                'globalAverage' => !empty($averages) ? round(array_sum($averages) / count($averages), 2) : null,
                'highestAverage' => !empty($averages) ? max($averages) : null,
                'lowestAverage' => !empty($averages) ? min($averages) : null,
                'progression' => $this->calculateProgression($averages),
            ],
            'gradesHistory' => $gradesHistory,
        ];
    }

    private function prepareClassData(array $bulletins): array
    {
        $averages = [];
        $mentions = [];
        $moduleStats = [];

        foreach ($bulletins as $bulletin) {
            if ($bulletin->getAverage() !== null) {
                $averages[] = $bulletin->getAverage();
            }
            $mention = $bulletin->getMention();
            $mentions[$mention] = ($mentions[$mention] ?? 0) + 1;

            foreach ($bulletin->getReportCardLines() as $line) {
                $module = $line->getModuleName();
                if (!isset($moduleStats[$module])) {
                    $moduleStats[$module] = ['grades' => [], 'sum' => 0, 'count' => 0];
                }
                if ($line->getNote() !== null) {
                    $moduleStats[$module]['grades'][] = $line->getNote();
                    $moduleStats[$module]['sum'] += $line->getNote();
                    $moduleStats[$module]['count']++;
                }
            }
        }

        $moduleAverages = [];
        foreach ($moduleStats as $module => $stats) {
            if ($stats['count'] > 0) {
                $moduleAverages[$module] = [
                    'average' => round($stats['sum'] / $stats['count'], 2),
                    'min' => min($stats['grades']),
                    'max' => max($stats['grades']),
                    'count' => $stats['count'],
                ];
            }
        }

        return [
            'statistics' => [
                'totalStudents' => count($bulletins),
                'classAverage' => !empty($averages) ? round(array_sum($averages) / count($averages), 2) : null,
                'highestAverage' => !empty($averages) ? max($averages) : null,
                'lowestAverage' => !empty($averages) ? min($averages) : null,
                'standardDeviation' => $this->calculateStdDev($averages),
                'mentionDistribution' => $mentions,
            ],
            'modulePerformance' => $moduleAverages,
        ];
    }

    private function buildAnalysisPrompt(array $studentData): string
    {
        $json = json_encode($studentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Analyse les données suivantes d'un étudiant et fournis une analyse détaillée en français.

DONNÉES DE L'ÉTUDIANT:
$json

ANALYSE DEMANDÉE:
1. **Résumé général**: Vue d'ensemble des performances (2-3 phrases)
2. **Points forts**: Modules ou périodes où l'étudiant excelle
3. **Points à améliorer**: Modules nécessitant une attention particulière
4. **Tendance d'évolution**: Analyse de la progression
5. **Score de performance**: Note globale sur 100 avec justification
PROMPT;
    }

    private function buildComparisonPrompt(array $studentsData): string
    {
        $json = json_encode($studentsData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Compare les performances des étudiants suivants et fournis une analyse en français.

DONNÉES:
$json

ANALYSE:
1. Classement global basé sur les moyennes
2. Forces relatives de chaque étudiant
3. Points communs et différences
4. Recommandations personnalisées
PROMPT;
    }

    private function buildRecommendationsPrompt(array $studentData): string
    {
        $json = json_encode($studentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Génère des recommandations personnalisées basées sur les données suivantes.

DONNÉES:
$json

GÉNÈRE:
1. **Recommandations académiques** (3-5 points)
2. **Méthodes d'étude suggérées**
3. **Objectifs SMART** (3 objectifs)
4. **Plan d'action prioritaire** (3 étapes)
PROMPT;
    }

    private function buildClassAnalysisPrompt(array $classData, string $year, string $semester): string
    {
        $json = json_encode($classData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Analyse les tendances de la classe pour la période $year - $semester.

DONNÉES:
$json

ANALYSE:
1. Vue d'ensemble de la performance globale
2. Distribution des résultats
3. Modules performants et en difficulté
4. Recommandations pédagogiques
PROMPT;
    }

    private function parseAnalysisResponse(string $response): array
    {
        $sections = [
            'summary' => '',
            'strengths' => '',
            'improvements' => '',
            'trend' => '',
            'score' => null,
            'full' => $response,
        ];

        if (preg_match('/(?:score|note)[^\d]*(\d+)/i', $response, $matches)) {
            $sections['score'] = (int) $matches[1];
        }

        return $sections;
    }

    private function calculateProgression(array $averages): ?string
    {
        if (count($averages) < 2) return null;

        $first = reset($averages);
        $last = end($averages);
        $diff = $last - $first;

        if ($diff > 1) return 'En progression (+' . round($diff, 2) . ')';
        elseif ($diff < -1) return 'En régression (' . round($diff, 2) . ')';
        else return 'Stable';
    }

    private function calculateStdDev(array $values): ?float
    {
        if (count($values) < 2) return null;

        $mean = array_sum($values) / count($values);
        $sumSquaredDiff = 0;

        foreach ($values as $value) {
            $sumSquaredDiff += pow($value - $mean, 2);
        }

        return round(sqrt($sumSquaredDiff / count($values)), 2);
    }
}
