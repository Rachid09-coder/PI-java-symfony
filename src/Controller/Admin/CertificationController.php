<?php

namespace App\Controller\Admin;

use App\Entity\Certification;
use App\Entity\User;
use App\Form\CertificationType;
use App\Repository\BulletinRepository;
use App\Repository\CertificationRepository;
use App\Service\AuditService;
use App\Service\HmacService;
use App\Service\PdfGeneratorService;
use App\Service\VerificationCodeService;
use App\Service\EmailService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/certification')]
class CertificationController extends AbstractController
{
    public function __construct(
        private AuditService $auditService,
        private HmacService $hmacService,
        private PdfGeneratorService $pdfService,
        private VerificationCodeService $verificationCodeService,
        private EmailService $emailService,
        private SmsService $smsService,
    ) {
    }

    private function getAuditUser(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * API: Get available semesters for a student (based on existing bulletins)
     */
    #[Route('/api/semesters/{studentId}', name: 'admin_certification_api_semesters', methods: ['GET'])]
    public function apiAvailableSemesters(int $studentId, BulletinRepository $bulletinRepo): JsonResponse
    {
        $bulletins = $bulletinRepo->findByStudentId($studentId);
        
        $semesters = [];
        $hasS1 = false;
        $hasS2 = false;
        
        foreach ($bulletins as $bulletin) {
            $sem = $bulletin->getSemester();
            if ($sem === 'S1' || $sem === 'Semestre 1') {
                $hasS1 = true;
            }
            if ($sem === 'S2' || $sem === 'Semestre 2') {
                $hasS2 = true;
            }
        }
        
        if ($hasS1) {
            $semesters[] = ['value' => 'S1', 'label' => 'Semestre 1'];
        }
        if ($hasS2) {
            $semesters[] = ['value' => 'S2', 'label' => 'Semestre 2'];
        }
        if ($hasS1 && $hasS2) {
            $semesters[] = ['value' => 'ANNUEL', 'label' => 'Annuel (Année complète)'];
        }
        
        return new JsonResponse($semesters);
    }

    #[Route('/', name: 'admin_certification_index')]
    public function index(Request $request, CertificationRepository $certificationRepo): Response 
    {
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'issuedAt');
        $sortOrder = $request->query->get('order', 'DESC');

        $certifications = $certificationRepo->searchAndSort($search ?: null, $sortBy, $sortOrder);

        return $this->render('admin/certification/index.html.twig', [
            'certifications' => $certifications,
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'admin_certification_new')]
    public function certificationNew(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        BulletinRepository $bulletinRepo
    ): Response {
        $certification = new Certification();
        $form = $this->createForm(CertificationType::class, $certification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Convertir la date texte validUntil en DateTimeImmutable
            $validUntilStr = $form->get('validUntil')->getData();
            if ($validUntilStr && is_string($validUntilStr)) {
                try {
                    $certification->setValidUntil(new \DateTimeImmutable($validUntilStr));
                } catch (\Exception $e) {
                    // Date invalide, ignorer
                }
            }
            
            // Auto-assign bulletin based on student + semester for NOTES and REUSSITE types
            $semesterChoice = $form->get('semesterChoice')->getData();
            $certType = $certification->getType();
            
            if (in_array($certType, ['NOTES', 'REUSSITE']) && $semesterChoice && $certification->getStudent()) {
                $studentId = $certification->getStudent()->getId();
                $bulletins = $bulletinRepo->findByStudentId($studentId);
                
                foreach ($bulletins as $bulletin) {
                    $sem = $bulletin->getSemester();
                    if (($semesterChoice === 'S1' && ($sem === 'S1' || $sem === 'Semestre 1')) ||
                        ($semesterChoice === 'S2' && ($sem === 'S2' || $sem === 'Semestre 2'))) {
                        $certification->setBulletin($bulletin);
                        break;
                    }
                }
                // For ANNUEL, we could link to both or just use the most recent
                if ($semesterChoice === 'ANNUEL' && !empty($bulletins)) {
                    $certification->setBulletin($bulletins[0]); // Most recent
                }
            }

            // Génération automatique du code de vérification
            if (!$certification->getVerificationCode()) {
                $certification->setVerificationCode($this->verificationCodeService->generateVerificationCode());
            }

            // Génération du numéro unique
            if (!$certification->getUniqueNumber()) {
                $certification->setUniqueNumber(
                    $this->verificationCodeService->generateForCertification($certification->getType())
                );
            }

            // Signature HMAC
            $certification->setHmacHash($this->hmacService->signCertification($certification));

            // Handle PDF upload
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile) {
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/certifications',
                        $newFilename
                    );
                    $certification->setPdfPath('uploads/certifications/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du PDF.');
                }
            }

            $em->persist($certification);
            $em->flush();

            $this->auditService->log('Certification', $certification->getId(), 'CREATED', $this->getAuditUser(), [
                'type' => $certification->getType(),
                'unique_number' => $certification->getUniqueNumber(),
            ]);

            $this->addFlash('success', 'Certification créée avec succès. N° : ' . $certification->getUniqueNumber());
            return $this->redirectToRoute('admin_certification_index');
        } elseif ($form->isSubmitted()) {
            // Debug: display validation errors
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getOrigin()->getName() . ': ' . $error->getMessage();
            }
            if (!empty($errors)) {
                $this->addFlash('error', 'Erreurs de validation: ' . implode(' | ', $errors));
            }
        }

        return $this->render('admin/certification/cert_form.html.twig', [
            'form' => $form,
            'certification' => $certification,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_certification_edit')]
    public function certificationEdit(
        Certification $certification,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        if ($certification->isRevoked()) {
            $this->addFlash('error', 'Cette certification est révoquée et ne peut plus être modifiée.');
            return $this->redirectToRoute('admin_certification_index');
        }

        $form = $this->createForm(CertificationType::class, $certification);
        
        // Pré-remplir la date validUntil en texte pour Flatpickr
        if ($certification->getValidUntil() && !$request->isMethod('POST')) {
            $form->get('validUntil')->setData($certification->getValidUntil()->format('Y-m-d'));
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Convertir la date texte validUntil en DateTimeImmutable
            $validUntilStr = $form->get('validUntil')->getData();
            if ($validUntilStr && is_string($validUntilStr)) {
                try {
                    $certification->setValidUntil(new \DateTimeImmutable($validUntilStr));
                } catch (\Exception $e) {
                    // Date invalide, ignorer
                }
            } elseif (empty($validUntilStr)) {
                $certification->setValidUntil(null);
            }
            
            // Handle PDF upload
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile) {
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/certifications',
                        $newFilename
                    );
                    $certification->setPdfPath('uploads/certifications/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du PDF.');
                }
            }

            // Re-signer le HMAC
            $certification->setHmacHash($this->hmacService->signCertification($certification));

            $em->flush();

            $this->auditService->log('Certification', $certification->getId(), 'UPDATED', $this->getAuditUser());

            $this->addFlash('success', 'Certification modifiée avec succès.');
            return $this->redirectToRoute('admin_certification_index');
        }

        return $this->render('admin/certification/cert_form.html.twig', [
            'form' => $form,
            'certification' => $certification,
        ]);
    }

    #[Route('/{id}/generate-pdf', name: 'admin_certification_generate_pdf', methods: ['POST'])]
    public function generatePdf(Certification $certification, Request $request, EntityManagerInterface $em): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $pdfPath = $this->pdfService->generateCertificationPdf($certification, $baseUrl);
        $certification->setPdfPath($pdfPath);
        $em->flush();

        $this->auditService->log('Certification', $certification->getId(), 'PDF_GENERATED', $this->getAuditUser());
        
        // Envoyer automatiquement par email à l'étudiant
        try {
            $this->emailService->sendCertificationEmail($certification);
            $this->addFlash('success', 'PDF de la certification généré et envoyé par email à l\'étudiant.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'PDF généré, mais l\'envoi d\'email a échoué: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_certification_index');
    }

    #[Route('/{id}/pdf', name: 'admin_certification_pdf', methods: ['GET'])]
    public function downloadPdf(Certification $certification): Response
    {
        if (!$certification->getPdfPath()) {
            $this->addFlash('error', 'Aucun PDF disponible.');
            return $this->redirectToRoute('admin_certification_index');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $certification->getPdfPath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier PDF est introuvable.');
            return $this->redirectToRoute('admin_certification_index');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'certification_' . $certification->getId() . '.pdf'
        );

        return $response;
    }

    #[Route('/{id}/revoke', name: 'admin_certification_revoke', methods: ['POST'])]
    public function revoke(Request $request, Certification $certification, EntityManagerInterface $em): Response
    {
        $reason = $request->request->get('reason', 'Aucune raison spécifiée');

        $certification->setStatus('REVOKED');
        $certification->setRevokedAt(new \DateTimeImmutable());
        $certification->setRevocationReason($reason);
        $em->flush();

        $this->auditService->log('Certification', $certification->getId(), 'REVOKED', $this->getAuditUser(), [
            'reason' => $reason,
        ]);

        $this->addFlash('success', 'Certification révoquée.');
        return $this->redirectToRoute('admin_certification_index');
    }

    #[Route('/{id}', name: 'admin_certification_show', methods: ['GET'])]
    public function certificationShow(Certification $certification): Response
    {
        return $this->render('admin/certification/show.html.twig', [
            'certification' => $certification,
        ]);
    }

    #[Route('/api/bulletins/{studentId}', name: 'admin_certification_api_bulletins', methods: ['GET'], priority: 10)]
    public function apiBulletins(int $studentId, Request $request, EntityManagerInterface $em): Response
    {
        $semester = $request->query->get('semester', '');
        
        $qb = $em->getRepository(\App\Entity\Bulletin::class)->createQueryBuilder('b')
            ->where('b.student = :studentId')
            ->setParameter('studentId', $studentId)
            ->orderBy('b.academicYear', 'DESC')
            ->addOrderBy('b.semester', 'DESC');
        
        // Filter by semester if provided
        if ($semester === 'S1') {
            $qb->andWhere('b.semester LIKE :sem')
               ->setParameter('sem', '%1%');
        } elseif ($semester === 'S2') {
            $qb->andWhere('b.semester LIKE :sem')
               ->setParameter('sem', '%2%');
        }
        // ANNUEL = show all bulletins
        
        $bulletins = $qb->getQuery()->getResult();
        
        $data = [];
        foreach ($bulletins as $bulletin) {
            $avg = $bulletin->getAverage() ? ' - Moy: ' . number_format($bulletin->getAverage(), 2) : '';
            $data[] = [
                'id' => $bulletin->getId(),
                'label' => $bulletin->getAcademicYear() . ' - ' . $bulletin->getSemester() . $avg,
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/{id}/send-sms', name: 'admin_certification_send_sms', methods: ['POST'])]
    public function sendSms(Certification $certification): Response
    {
        if (!$this->smsService->isConfigured()) {
            $this->addFlash('error', 'Service SMS non configuré. Vérifiez les clés Twilio dans .env');
            return $this->redirectToRoute('admin_certification_show', ['id' => $certification->getId()]);
        }

        $result = $this->smsService->notifyCertificationReady($certification);

        if ($result['success']) {
            $this->addFlash('success', 'SMS envoyé avec succès à l\'étudiant.');
        } else {
            $this->addFlash('error', 'Erreur envoi SMS: ' . ($result['error'] ?? 'Erreur inconnue'));
        }

        return $this->redirectToRoute('admin_certification_show', ['id' => $certification->getId()]);
    }

    #[Route('/{id}/send-email', name: 'admin_certification_send_email', methods: ['POST'])]
    public function sendEmail(Certification $certification): Response
    {
        try {
            $this->emailService->sendCertificationEmail($certification);
            $this->addFlash('success', 'Email envoyé avec succès à l\'étudiant.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur envoi email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_certification_show', ['id' => $certification->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_certification_delete', methods: ['POST'])]
    public function delete(Certification $certification, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $certification->getId(), $request->request->get('_token'))) {
            $this->auditService->log('Certification', $certification->getId(), 'DELETED', $this->getAuditUser(), [
                'type' => $certification->getType(),
                'unique_number' => $certification->getUniqueNumber(),
                'student' => $certification->getStudent()->getPrenom() . ' ' . $certification->getStudent()->getName(),
            ]);

            $em->remove($certification);
            $em->flush();

            $this->addFlash('success', 'Certification supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_certification_index');
    }
}
