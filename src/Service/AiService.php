<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\BulletinRepository;
use App\Repository\CertificationRepository;
use App\Repository\CourseRepository;
use App\Repository\GradeRepository;
use App\Repository\UserRepository;
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
        private UserRepository $userRepository,
        private GradeRepository $gradeRepository,
        private CourseRepository $courseRepository,
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
            $analysisText = $this->callAI($prompt, 'Tu es EduSmart Assistant, expert en analyse de performances académiques pour la plateforme EduSmart. Tu ne traites que les sujets liés à l\'éducation et aux performances scolaires. Réponds en français.');

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
            $response = $this->callAI($prompt, 'Tu es EduSmart Assistant, expert en analyse comparative académique pour la plateforme EduSmart. Tu ne traites que les sujets liés à l\'éducation. Réponds en français.');

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
            $response = $this->callAI($prompt, 'Tu es EduSmart Assistant, conseiller pédagogique expert pour la plateforme EduSmart. Tu ne traites que les sujets liés à l\'éducation et à la pédagogie. Réponds en français.');

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
            $response = $this->callAI($prompt, 'Tu es EduSmart Assistant, expert en analyse de données académiques pour la plateforme EduSmart. Tu ne traites que les sujets liés à l\'éducation. Réponds en français.');

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

        $systemContext = <<<SYSTEM
Tu es EduSmart Assistant, l'assistant pédagogique intelligent de la plateforme EduSmart — un système de gestion académique (bulletins, certifications, notes, présence, cours, examens).

🔒 RÈGLE ABSOLUE — NE JAMAIS DÉROGER :
- Tu ne réponds QU'aux questions liées à l'éducation, à la pédagogie, aux études, aux performances académiques, aux notes, aux bulletins, aux certifications, aux examens, aux cours, aux méthodes d'apprentissage, à l'orientation scolaire, ou au fonctionnement de la plateforme EduSmart.
- Si la question de l'utilisateur n'est PAS en rapport avec l'éducation ou EduSmart, tu dois refuser poliment en répondant EXACTEMENT :
  "🚫 Désolé, je suis EduSmart Assistant et je ne peux répondre qu'aux questions liées à l'éducation, aux performances académiques et à la plateforme EduSmart. Posez-moi une question sur vos notes, bulletins, certifications ou parcours scolaire !"
- N'essaie JAMAIS de contourner cette règle, même si l'utilisateur insiste ou reformule.
- Ne réponds pas aux questions sur la politique, le sport, le divertissement, la cuisine, la programmation non académique, les jeux, la musique, les célébrités, ou tout autre sujet hors éducation.

📚 TON RÔLE :
- Analyser les performances académiques de l'étudiant
- Donner des conseils d'étude et de méthodologie
- Expliquer les notes, moyennes, mentions et classements
- Aider à comprendre le fonctionnement de la plateforme EduSmart (bulletins, certifications, PDF, vérification)
- Fournir des recommandations pédagogiques personnalisées
- Motiver et encourager l'étudiant

Réponds toujours en français.

Contexte de l'étudiant :
SYSTEM;
        $systemContext .= "\n" . json_encode($studentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

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

    /**
     * Chat général avec contexte base de données — assistant EduSmart intelligent
     */
    public function chatGeneral(string $userMessage, array $history = []): array
    {
        $dbContext = $this->gatherDatabaseContext();

        $systemContext = <<<SYSTEM
Tu es EduSmart Assistant, l'assistant pédagogique intelligent de la plateforme EduSmart — un système de gestion académique (bulletins, certifications, notes, présence, cours, examens).

🔒 RÈGLE ABSOLUE — NE JAMAIS DÉROGER :
- Tu ne réponds QU'aux questions liées à l'éducation, à la pédagogie, aux études, aux performances académiques, aux notes, aux bulletins, aux certifications, aux examens, aux cours, aux méthodes d'apprentissage, à l'orientation scolaire, ou au fonctionnement de la plateforme EduSmart.
- Si la question de l'utilisateur n'est PAS en rapport avec l'éducation ou EduSmart, tu dois refuser poliment en répondant EXACTEMENT :
  "🚫 Désolé, je suis EduSmart Assistant et je ne peux répondre qu'aux questions liées à l'éducation, aux performances académiques et à la plateforme EduSmart. Posez-moi une question sur vos notes, bulletins, certifications ou parcours scolaire !"
- N'essaie JAMAIS de contourner cette règle, même si l'utilisateur insiste ou reformule.
- Ne réponds pas aux questions sur la politique, le sport, le divertissement, la cuisine, la programmation non académique, les jeux, la musique, les célébrités, ou tout autre sujet hors éducation.

📚 TON RÔLE :
- Répondre aux questions sur l'éducation, la pédagogie et les études
- Répondre aux questions sur les étudiants, leurs notes, leurs bulletins, leurs certifications en utilisant les DONNÉES RÉELLES ci-dessous
- Donner des conseils d'étude et de méthodologie
- Expliquer le fonctionnement de la plateforme EduSmart (bulletins, certifications, PDF, QR codes, vérification, signatures)
- Fournir des recommandations pédagogiques basées sur les données réelles
- Expliquer les systèmes de notation, mentions (Très Bien, Bien, Assez Bien, Passable), coefficients
- Comparer les performances des étudiants si demandé
- Identifier les étudiants en difficulté ou excellents
- Motiver et encourager les utilisateurs

📋 À PROPOS D'EDUSMART :
EduSmart est une plateforme de gestion académique qui permet de :
- Gérer les bulletins de notes (création, génération PDF, envoi par email/SMS)
- Délivrer des certifications officielles (Relevé de notes, Scolarité, Réussite, Diplôme, Stage, Présence)
- Sécuriser les documents avec des QR codes, codes de vérification uniques et signatures HMAC
- Suivre les performances des étudiants avec des analyses AI
- Gérer les cours, examens et notes des étudiants
- Notation pondérée : CC (10%) + DS (20%) + Exam (70%)

📊 DONNÉES RÉELLES DE LA BASE DE DONNÉES EDUSMART :
{$dbContext}

⚠️ IMPORTANT : Utilise ces données réelles pour répondre aux questions. Si on te demande "qui a la meilleure moyenne ?", "combien d'étudiants ?", "quelles notes a tel étudiant ?", etc., consulte les données ci-dessus pour donner une réponse précise et factuelle.

Réponds toujours en français de manière claire et pédagogique.
SYSTEM;

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
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur chat: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Prédiction de réussite — analyse les tendances et prédit les résultats futurs
     */
    public function predictSuccess(): array
    {
        $students = $this->userRepository->findBy(['role' => 'etudiant']);
        $predictions = [];

        foreach ($students as $student) {
            $bulletins = $this->bulletinRepository->findByStudentId($student->getId());
            if (empty($bulletins)) continue;

            $averages = array_filter(array_map(fn($b) => $b->getAverage(), $bulletins));
            if (empty($averages)) continue;

            $globalAvg = round(array_sum($averages) / count($averages), 2);
            $lastBulletin = end($bulletins);
            $moduleDetails = [];

            foreach ($lastBulletin->getReportCardLines() as $line) {
                $moduleDetails[] = [
                    'module' => $line->getModuleName(),
                    'note' => $line->getNote(),
                    'noteCC' => $line->getNoteCC(),
                    'noteDS' => $line->getNoteDS(),
                    'noteExam' => $line->getNoteExam(),
                ];
            }

            $predictions[] = [
                'name' => $student->getPrenom() . ' ' . $student->getName(),
                'globalAverage' => $globalAvg,
                'lastAverage' => $lastBulletin->getAverage(),
                'mention' => $lastBulletin->getMention(),
                'totalBulletins' => count($bulletins),
                'progression' => $this->calculateProgression(array_values($averages)),
                'modules' => $moduleDetails,
            ];
        }

        if (empty($predictions)) {
            return ['success' => false, 'error' => 'Aucune donnée disponible pour la prédiction'];
        }

        $json = json_encode($predictions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
En te basant sur les données académiques réelles suivantes, fais une PRÉDICTION DE RÉUSSITE pour chaque étudiant.

DONNÉES DES ÉTUDIANTS:
{$json}

POUR CHAQUE ÉTUDIANT, FOURNIS:
1. 🎯 **Probabilité de réussite** (en pourcentage estimé)
2. 📊 **Niveau de risque** : ✅ Faible / ⚠️ Moyen / 🔴 Élevé
3. 📈 **Tendance** : En progression / Stable / En régression
4. 💡 **Actions prioritaires** pour améliorer les chances de réussite

Termine par un 📋 **RÉSUMÉ GLOBAL** : combien d'étudiants sont à risque, combien sont en bonne voie, et les actions collectives recommandées.

Sois précis, utilise les vrais noms et les vraies notes. Réponds en français.
PROMPT;

        try {
            $response = $this->callAI($prompt, 'Tu es EduSmart Assistant, expert en analyse prédictive académique. Tu analyses les données réelles pour prédire les résultats futurs des étudiants. Réponds en français.');

            return [
                'success' => true,
                'predictions' => $response,
                'studentsAnalyzed' => count($predictions),
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Détection d'anomalies — identifie les incohérences dans les notes
     */
    public function detectAnomalies(): array
    {
        $bulletins = $this->bulletinRepository->findAll();
        $anomalyData = [];

        foreach ($bulletins as $bulletin) {
            $studentName = $bulletin->getStudent()
                ? $bulletin->getStudent()->getPrenom() . ' ' . $bulletin->getStudent()->getName()
                : 'Inconnu';

            $lines = [];
            foreach ($bulletin->getReportCardLines() as $line) {
                $lines[] = [
                    'module' => $line->getModuleName(),
                    'noteCC' => $line->getNoteCC(),
                    'noteDS' => $line->getNoteDS(),
                    'noteExam' => $line->getNoteExam(),
                    'noteFinal' => $line->getNote(),
                    'coefficient' => $line->getCoefficient(),
                ];
            }

            $anomalyData[] = [
                'student' => $studentName,
                'period' => $bulletin->getAcademicYear() . ' - ' . $bulletin->getSemester(),
                'average' => $bulletin->getAverage(),
                'mention' => $bulletin->getMention(),
                'rank' => $bulletin->getClassRank(),
                'status' => $bulletin->getStatus(),
                'modules' => $lines,
            ];
        }

        if (empty($anomalyData)) {
            return ['success' => false, 'error' => 'Aucun bulletin disponible'];
        }

        $json = json_encode($anomalyData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
Analyse les bulletins suivants pour DÉTECTER DES ANOMALIES et incohérences.

DONNÉES DES BULLETINS:
{$json}

DÉTECTE ET SIGNALE:
1. 🔍 **Écarts suspects** : Notes CC très élevées mais Exam très basses (ou inversement)
2. 📉 **Chutes brutales** : Baisse significative d'un étudiant entre périodes
3. ⚠️ **Incohérences** : Moyennes mal calculées, mentions non conformes aux notes
4. 🎯 **Notes extrêmes** : Notes de 0 ou 20 parfaites qui méritent vérification
5. 📊 **Patterns suspects** : Notes identiques sur plusieurs modules, résultats statistiquement improbables

Pour chaque anomalie trouvée, indique:
- 👤 L'étudiant concerné
- 📚 Le module/période
- 🔴 Le type d'anomalie
- 💡 La recommandation

Termine par un résumé : combien d'anomalies détectées et à quel niveau de gravité.

Réponds en français. Sois factuel et précis.
PROMPT;

        try {
            $response = $this->callAI($prompt, 'Tu es EduSmart Assistant, expert en audit et contrôle qualité des données académiques. Tu détectes les incohérences et anomalies. Réponds en français.');

            return [
                'success' => true,
                'anomalies' => $response,
                'bulletinsAnalyzed' => count($anomalyData),
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Bilan des certifications — audit complet des certifications émises
     */
    public function auditCertifications(): array
    {
        $certifications = $this->certificationRepository->findAll();
        $students = $this->userRepository->findBy(['role' => 'etudiant']);

        if (empty($certifications)) {
            return ['success' => false, 'error' => 'Aucune certification trouvée'];
        }

        $certData = [];
        foreach ($certifications as $cert) {
            $studentName = $cert->getStudent()
                ? $cert->getStudent()->getPrenom() . ' ' . $cert->getStudent()->getName()
                : 'Inconnu';

            // Get student's average from bulletins
            $studentAvg = null;
            if ($cert->getStudent()) {
                $bulletins = $this->bulletinRepository->findByStudentId($cert->getStudent()->getId());
                $avgs = array_filter(array_map(fn($b) => $b->getAverage(), $bulletins));
                if (!empty($avgs)) {
                    $studentAvg = round(array_sum($avgs) / count($avgs), 2);
                }
            }

            $certData[] = [
                'student' => $studentName,
                'type' => $cert->getType(),
                'typeLabel' => $cert->getTypeLabel(),
                'status' => $cert->getStatus(),
                'issuedAt' => $cert->getIssuedAt()->format('d/m/Y'),
                'validUntil' => $cert->getValidUntil() ? $cert->getValidUntil()->format('d/m/Y') : 'Illimité',
                'isRevoked' => $cert->isRevoked(),
                'studentAverage' => $studentAvg,
            ];
        }

        // Build summary statistics
        $byType = [];
        $byStatus = [];
        foreach ($certData as $c) {
            $byType[$c['typeLabel']] = ($byType[$c['typeLabel']] ?? 0) + 1;
            $byStatus[$c['status']] = ($byStatus[$c['status']] ?? 0) + 1;
        }

        $json = json_encode([
            'certifications' => $certData,
            'statistics' => [
                'total' => count($certData),
                'byType' => $byType,
                'byStatus' => $byStatus,
                'totalStudents' => count($students),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
Réalise un BILAN COMPLET des certifications émises sur la plateforme EduSmart.

DONNÉES:
{$json}

ANALYSE DEMANDÉE:
1. 📊 **Statistiques générales** : Total, répartition par type, par statut
2. 🏆 **Top certifications** : Types les plus délivrés et pourquoi
3. 👥 **Couverture étudiante** : Quel pourcentage d'étudiants a reçu des certifications ?
4. ⚠️ **Alertes** : Certifications révoquées, expirées, ou étudiants sans certification
5. 🔍 **Cohérence** : Les certifications de réussite correspondent-elles aux moyennes des étudiants ?
6. 💡 **Recommandations** : Quelles certifications manquent ? Quels étudiants méritent d'être certifiés ?
7. 📈 **Indicateurs de qualité** : Taux de certification, diversité des types émis

Réponds en français, de manière structurée et professionnelle.
PROMPT;

        try {
            $response = $this->callAI($prompt, 'Tu es EduSmart Assistant, expert en audit et gestion documentaire académique. Tu analyses les certifications pour détecter les opportunités et anomalies. Réponds en français.');

            return [
                'success' => true,
                'audit' => $response,
                'stats' => [
                    'total' => count($certData),
                    'byType' => $byType,
                    'byStatus' => $byStatus,
                ],
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Suggestions de certifications — recommande les certifications éligibles par étudiant
     */
    public function suggestCertifications(): array
    {
        $students = $this->userRepository->findBy(['role' => 'etudiant']);
        $eligibilityData = [];

        foreach ($students as $student) {
            $bulletins = $this->bulletinRepository->findByStudentId($student->getId());
            $certifications = $this->certificationRepository->findBy(['student' => $student]);

            $existingTypes = array_map(fn($c) => $c->getType(), $certifications);
            $averages = array_filter(array_map(fn($b) => $b->getAverage(), $bulletins));
            $globalAvg = !empty($averages) ? round(array_sum($averages) / count($averages), 2) : null;

            // Get mention from latest bulletin
            $lastMention = null;
            if (!empty($bulletins)) {
                $lastBulletin = end($bulletins);
                $lastMention = $lastBulletin->getMention();
            }

            $eligibilityData[] = [
                'name' => $student->getPrenom() . ' ' . $student->getName(),
                'email' => $student->getEmail(),
                'globalAverage' => $globalAvg,
                'lastMention' => $lastMention,
                'totalBulletins' => count($bulletins),
                'existingCertifications' => $existingTypes,
                'allCertificationTypes' => ['SCOLARITE', 'REUSSITE', 'NOTES', 'DIPLOME', 'STAGE', 'PRESENCE'],
            ];
        }

        if (empty($eligibilityData)) {
            return ['success' => false, 'error' => 'Aucun étudiant trouvé'];
        }

        $json = json_encode($eligibilityData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
En te basant sur les données académiques suivantes, RECOMMANDE les certifications que chaque étudiant devrait recevoir.

TYPES DE CERTIFICATIONS DISPONIBLES SUR EDUSMART:
- SCOLARITE : Attestation de scolarité (tout étudiant inscrit)
- REUSSITE : Certificat de réussite (moyenne >= 10/20)
- NOTES : Relevé de notes (tout étudiant ayant des bulletins)
- DIPLOME : Diplôme interne (moyenne >= 14/20 avec mention Bien ou Très Bien)
- STAGE : Attestation de stage (si applicable)
- PRESENCE : Attestation de présence (tout étudiant inscrit)

DONNÉES DES ÉTUDIANTS:
{$json}

POUR CHAQUE ÉTUDIANT:
1. ✅ **Certifications éligibles** qu'il n'a PAS encore
2. 🎯 **Priorité** : Haute / Moyenne / Basse
3. 💡 **Justification** basée sur les notes et la moyenne
4. ⚠️ **Manques** : Ce qui empêche l'obtention de certaines certifications

Termine par un 📋 **PLAN D'ACTION** global pour l'émission des certifications manquantes.

Sois précis et utilise les vrais noms et données. Réponds en français.
PROMPT;

        try {
            $response = $this->callAI($prompt, 'Tu es EduSmart Assistant, expert en certification académique et gestion documentaire. Tu recommandes les certifications appropriées. Réponds en français.');

            return [
                'success' => true,
                'suggestions' => $response,
                'studentsAnalyzed' => count($eligibilityData),
                'generatedAt' => (new \DateTimeImmutable())->format('c'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Rassemble les données de la base de données pour le contexte AI
     */
    private function gatherDatabaseContext(): string
    {
        $context = '';

        // 1. Les étudiants
        $students = $this->userRepository->findBy(['role' => 'etudiant'], ['name' => 'ASC']);
        $context .= "=== ÉTUDIANTS ({count} total) ===\n";
        $context = str_replace('{count}', (string) count($students), $context);

        foreach ($students as $student) {
            $context .= "- {$student->getPrenom()} {$student->getName()} (ID: {$student->getId()}, Email: {$student->getEmail()}, Tél: {$student->getNumtel()})\n";
        }

        // 2. Les bulletins avec détails des notes
        $bulletins = $this->bulletinRepository->findAll();
        $context .= "\n=== BULLETINS (" . count($bulletins) . " total) ===\n";

        foreach ($bulletins as $bulletin) {
            $studentName = $bulletin->getStudent() 
                ? $bulletin->getStudent()->getPrenom() . ' ' . $bulletin->getStudent()->getName() 
                : 'Inconnu';
            $context .= "\n📄 Bulletin de {$studentName} — {$bulletin->getAcademicYear()} {$bulletin->getSemester()}\n";
            $context .= "   Moyenne: {$bulletin->getAverage()}/20 | Mention: {$bulletin->getMention()} | Rang: {$bulletin->getClassRank()} | Statut: {$bulletin->getStatus()}\n";

            // Détails des matières (ReportCardLines)
            $lines = $bulletin->getReportCardLines();
            if (count($lines) > 0) {
                $context .= "   Détail des notes:\n";
                foreach ($lines as $line) {
                    $context .= "     • {$line->getModuleName()}: CC={$line->getNoteCC()}, DS={$line->getNoteDS()}, Exam={$line->getNoteExam()}, Note finale={$line->getNote()}/20 (coeff {$line->getCoefficient()})\n";
                }
            }
        }

        // 3. Les certifications
        $certifications = $this->certificationRepository->findAll();
        $context .= "\n=== CERTIFICATIONS (" . count($certifications) . " total) ===\n";

        $certByType = [];
        foreach ($certifications as $cert) {
            $type = $cert->getType();
            if (!isset($certByType[$type])) {
                $certByType[$type] = 0;
            }
            $certByType[$type]++;
        }

        foreach ($certByType as $type => $count) {
            $context .= "- {$type}: {$count} certification(s)\n";
        }

        foreach ($certifications as $cert) {
            $studentName = $cert->getStudent()
                ? $cert->getStudent()->getPrenom() . ' ' . $cert->getStudent()->getName()
                : 'Inconnu';
            $context .= "  • {$cert->getTypeLabel()} pour {$studentName} — Statut: {$cert->getStatus()} | Émis le: {$cert->getIssuedAt()->format('d/m/Y')}\n";
        }

        // 4. Les cours
        $courses = $this->courseRepository->findAll();
        $context .= "\n=== COURS (" . count($courses) . " total) ===\n";
        foreach ($courses as $course) {
            $context .= "- {$course->getTitle()} (Coefficient: {$course->getCoefficient()}, Statut: {$course->getStatus()})\n";
        }

        // 5. Les notes (Grades) — résumé par étudiant
        $grades = $this->gradeRepository->findAll();
        $context .= "\n=== NOTES/GRADES (" . count($grades) . " total) ===\n";

        $gradesByStudent = [];
        foreach ($grades as $grade) {
            $studentName = $grade->getStudent()
                ? $grade->getStudent()->getPrenom() . ' ' . $grade->getStudent()->getName()
                : 'Inconnu';
            if (!isset($gradesByStudent[$studentName])) {
                $gradesByStudent[$studentName] = [];
            }
            $gradesByStudent[$studentName][] = [
                'module' => $grade->getModule() ? $grade->getModule()->getTitle() : 'N/A',
                'note' => $grade->getNote(),
                'coefficient' => $grade->getCoefficient(),
                'session' => $grade->getSession(),
                'year' => $grade->getAcademicYear(),
                'semester' => $grade->getSemester(),
            ];
        }

        foreach ($gradesByStudent as $studentName => $studentGrades) {
            $totalWeighted = 0;
            $totalCoeff = 0;
            foreach ($studentGrades as $g) {
                $totalWeighted += $g['note'] * $g['coefficient'];
                $totalCoeff += $g['coefficient'];
            }
            $avg = $totalCoeff > 0 ? round($totalWeighted / $totalCoeff, 2) : 0;

            $context .= "\n📊 {$studentName} (Moyenne pondérée des grades: {$avg}/20)\n";
            foreach ($studentGrades as $g) {
                $context .= "   • {$g['module']}: {$g['note']}/20 (coeff {$g['coefficient']}, {$g['session']}, {$g['year']} {$g['semester']})\n";
            }
        }

        // 6. Statistiques globales
        $context .= "\n=== STATISTIQUES GLOBALES ===\n";
        $context .= "- Nombre total d'étudiants: " . count($students) . "\n";
        $context .= "- Nombre total de bulletins: " . count($bulletins) . "\n";
        $context .= "- Nombre total de certifications: " . count($certifications) . "\n";
        $context .= "- Nombre total de cours: " . count($courses) . "\n";
        $context .= "- Nombre total de notes: " . count($grades) . "\n";

        // Moyennes globales depuis les bulletins
        $allAverages = array_filter(array_map(fn($b) => $b->getAverage(), $bulletins), fn($a) => $a !== null);
        if (count($allAverages) > 0) {
            $globalAvg = round(array_sum($allAverages) / count($allAverages), 2);
            $bestAvg = max($allAverages);
            $worstAvg = min($allAverages);
            $context .= "- Moyenne générale (tous bulletins): {$globalAvg}/20\n";
            $context .= "- Meilleure moyenne: {$bestAvg}/20\n";
            $context .= "- Plus basse moyenne: {$worstAvg}/20\n";
        }

        return $context;
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
