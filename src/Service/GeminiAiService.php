<?php

namespace App\Service;

use App\Entity\Bulletin;
use App\Entity\User;
use App\Repository\BulletinRepository;
use App\Repository\CertificationRepository;
use Gemini\Client;
use Gemini\Data\Content;
use Gemini\Enums\Role;

/**
 * Service d'analyse de performance utilisant Google Gemini AI
 */
class GeminiAiService
{
    private ?Client $client = null;
    private string $apiKey;
    private string $model = 'models/gemini-1.5-flash';

    public function __construct(
        private BulletinRepository $bulletinRepository,
        private CertificationRepository $certificationRepository,
        string $geminiApiKey = ''
    ) {
        $this->apiKey = $geminiApiKey ?: ($_ENV['GEMINI_API_KEY'] ?? '');
    }

    /**
     * Initialise le client Gemini
     */
    private function getClient(): Client
    {
        if ($this->client === null) {
            if (empty($this->apiKey) || $this->apiKey === 'your_gemini_api_key_here') {
                throw new \RuntimeException('Clé API Gemini non configurée. Ajoutez GEMINI_API_KEY dans votre fichier .env');
            }
            $this->client = \Gemini::client($this->apiKey);
        }
        return $this->client;
    }

    /**
     * Analyse les performances d'un étudiant
     */
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

        // Préparer les données pour l'analyse
        $studentData = $this->prepareStudentData($student, $bulletins, $certifications);
        
        // Créer le prompt pour Gemini
        $prompt = $this->buildAnalysisPrompt($studentData);

        try {
            $response = $this->getClient()
                ->generativeModel($this->model)
                ->generateContent($prompt);

            $analysisText = $response->text();

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

    /**
     * Compare les performances de plusieurs étudiants
     */
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
                'error' => 'Au moins 2 étudiants avec des bulletins sont nécessaires pour la comparaison',
            ];
        }

        $prompt = $this->buildComparisonPrompt($studentsData);

        try {
            $response = $this->getClient()
                ->generativeModel($this->model)
                ->generateContent($prompt);

            return [
                'success' => true,
                'studentsCompared' => count($studentsData),
                'comparison' => $response->text(),
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la comparaison AI: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Génère des recommandations personnalisées
     */
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
            $response = $this->getClient()
                ->generativeModel($this->model)
                ->generateContent($prompt);

            return [
                'success' => true,
                'student' => $student->getPrenom() . ' ' . $student->getName(),
                'recommendations' => $response->text(),
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Analyse les tendances globales de la classe
     */
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
            $response = $this->getClient()
                ->generativeModel($this->model)
                ->generateContent($prompt);

            return [
                'success' => true,
                'period' => $academicYear . ' - ' . $semesterValue,
                'studentsAnalyzed' => count($bulletins),
                'analysis' => $response->text(),
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

    /**
     * Chat interactif avec contexte étudiant
     */
    public function chat(User $student, string $userMessage, array $history = []): array
    {
        $bulletins = $this->bulletinRepository->findByStudentId($student->getId());
        $studentData = $this->prepareStudentData($student, $bulletins, []);

        $systemContext = "Tu es un assistant pédagogique pour EduSmart. Tu analyses les performances académiques.\n";
        $systemContext .= "Contexte de l'étudiant:\n" . json_encode($studentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $contents = [];
        
        // Ajouter le contexte système
        $contents[] = Content::parse(part: $systemContext, role: Role::USER);
        $contents[] = Content::parse(part: "Compris, je suis prêt à aider avec l'analyse des performances de " . $student->getPrenom(), role: Role::MODEL);

        // Ajouter l'historique de conversation
        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? Role::USER : Role::MODEL;
            $contents[] = Content::parse(part: $msg['content'], role: $role);
        }

        // Ajouter le nouveau message
        $contents[] = Content::parse(part: $userMessage, role: Role::USER);

        try {
            $response = $this->getClient()
                ->generativeModel($this->model)
                ->generateContent(...$contents);

            return [
                'success' => true,
                'response' => $response->text(),
                'studentContext' => $student->getPrenom() . ' ' . $student->getName(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur chat: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Prépare les données d'un étudiant pour l'analyse
     */
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

        // Calculer les statistiques
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

    /**
     * Prépare les données de classe pour l'analyse
     */
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

        // Calculer moyennes par module
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

    /**
     * Construit le prompt d'analyse de performance
     */
    private function buildAnalysisPrompt(array $studentData): string
    {
        $json = json_encode($studentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Tu es un expert en analyse de performances académiques pour l'application EduSmart.
Analyse les données suivantes d'un étudiant et fournis une analyse détaillée en français.

DONNÉES DE L'ÉTUDIANT:
$json

ANALYSE DEMANDÉE:
1. **Résumé général**: Vue d'ensemble des performances (2-3 phrases)
2. **Points forts**: Modules ou périodes où l'étudiant excelle
3. **Points à améliorer**: Modules ou domaines nécessitant une attention particulière
4. **Tendance d'évolution**: Analyse de la progression au fil du temps
5. **Score de performance**: Note globale sur 100 avec justification

Format ta réponse de manière structurée avec les sections ci-dessus.
Sois précis et constructif dans tes observations.
PROMPT;
    }

    /**
     * Construit le prompt de comparaison
     */
    private function buildComparisonPrompt(array $studentsData): string
    {
        $json = json_encode($studentsData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Tu es un expert en analyse comparative de performances académiques.
Compare les performances des étudiants suivants et fournis une analyse en français.

DONNÉES DES ÉTUDIANTS:
$json

ANALYSE COMPARATIVE:
1. Classement global basé sur les moyennes
2. Forces relatives de chaque étudiant
3. Points communs et différences
4. Recommandations personnalisées pour chacun

Sois objectif et constructif.
PROMPT;
    }

    /**
     * Construit le prompt de recommandations
     */
    private function buildRecommendationsPrompt(array $studentData): string
    {
        $json = json_encode($studentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Tu es un conseiller pédagogique expert pour EduSmart.
Génère des recommandations personnalisées basées sur les données suivantes.

DONNÉES DE L'ÉTUDIANT:
$json

GÉNÈRE:
1. **Recommandations académiques** (3-5 points): Actions concrètes pour améliorer les notes
2. **Méthodes d'étude suggérées**: Techniques adaptées au profil
3. **Objectifs SMART**: 3 objectifs spécifiques, mesurables et atteignables
4. **Ressources recommandées**: Types de ressources utiles
5. **Plan d'action prioritaire**: Les 3 premières étapes à suivre

Sois encourageant mais réaliste. Fournis des conseils actionnables.
PROMPT;
    }

    /**
     * Construit le prompt d'analyse de classe
     */
    private function buildClassAnalysisPrompt(array $classData, string $year, string $semester): string
    {
        $json = json_encode($classData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Tu es un expert en analyse de données académiques pour EduSmart.
Analyse les tendances de la classe pour la période $year - $semester.

DONNÉES DE LA CLASSE:
$json

ANALYSE DEMANDÉE:
1. **Vue d'ensemble**: Résumé de la performance globale de la classe
2. **Distribution des résultats**: Analyse de la répartition des mentions
3. **Modules performants**: Identifier les matières où la classe excelle
4. **Modules en difficulté**: Matières nécessitant une attention particulière
5. **Recommandations pédagogiques**: Suggestions pour les enseignants
6. **Indicateurs clés**: Métriques importantes à surveiller

Fournis une analyse professionnelle et actionnable.
PROMPT;
    }

    /**
     * Parse la réponse d'analyse en sections
     */
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

        // Extraire le score si présent
        if (preg_match('/(?:score|note)[^\d]*(\d+)/i', $response, $matches)) {
            $sections['score'] = (int) $matches[1];
        }

        // Tentative d'extraction des sections (basique)
        $patterns = [
            'summary' => '/résumé\s*(?:général)?[:\s]*(.+?)(?=\*\*|points?\s*forts?|$)/is',
            'strengths' => '/points?\s*forts?[:\s]*(.+?)(?=\*\*|points?\s*à|amélior|$)/is',
            'improvements' => '/(?:points?\s*à\s*améliorer|améliorations?)[:\s]*(.+?)(?=\*\*|tendance|évolution|$)/is',
            'trend' => '/(?:tendance|évolution)[:\s]*(.+?)(?=\*\*|score|note|$)/is',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                $sections[$key] = trim($matches[1]);
            }
        }

        return $sections;
    }

    /**
     * Calcule la progression (différence entre première et dernière moyenne)
     */
    private function calculateProgression(array $averages): ?string
    {
        if (count($averages) < 2) {
            return null;
        }

        $first = reset($averages);
        $last = end($averages);
        $diff = $last - $first;

        if ($diff > 1) {
            return 'En progression (+' . round($diff, 2) . ')';
        } elseif ($diff < -1) {
            return 'En régression (' . round($diff, 2) . ')';
        } else {
            return 'Stable';
        }
    }

    /**
     * Calcule l'écart-type
     */
    private function calculateStdDev(array $values): ?float
    {
        if (count($values) < 2) {
            return null;
        }

        $mean = array_sum($values) / count($values);
        $sumSquaredDiff = 0;

        foreach ($values as $value) {
            $sumSquaredDiff += pow($value - $mean, 2);
        }

        return round(sqrt($sumSquaredDiff / count($values)), 2);
    }
}
