<?php

namespace App\Controller;

use App\Entity\Bulletin;
use App\Entity\ReportCardLine;
use App\Form\BulletinType;
use App\Repository\BulletinRepository;
use App\Service\AuditService;
use App\Service\BulletinWorkflowService;
use App\Service\PdfGeneratorService;
use App\Service\EmailService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/bulletin', name: 'admin_bulletin_')]
class BulletinController extends AbstractController
{
    public function __construct(
        private BulletinWorkflowService $workflowService,
        private PdfGeneratorService $pdfService,
        private AuditService $auditService,
        private EmailService $emailService,
        private BulletinRepository $bulletinRepository,
        private SmsService $smsService,
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, BulletinRepository $bulletinRepository): Response
    {
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'createdAt');
        $sortOrder = $request->query->get('order', 'DESC');

        $bulletins = $bulletinRepository->searchAndSort($search ?: null, $sortBy, $sortOrder);

        return $this->render('bulletin/index.html.twig', [
            'bulletins' => $bulletins,
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $bulletin = new Bulletin();
        $form = $this->createForm(BulletinType::class, $bulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Calculer le rang automatiquement
            $em->persist($bulletin);
            $em->flush();
            
            // Recalculer tous les rangs de la même période
            $this->bulletinRepository->recalculateAllRanks(
                $bulletin->getAcademicYear(),
                $bulletin->getSemester()
            );
            $em->flush();

            $this->auditService->log('Bulletin', $bulletin->getId(), 'CREATED', $this->getUser());
            $this->addFlash('success', 'Bulletin créé avec succès. Rang calculé automatiquement.');

            return $this->redirectToRoute('admin_bulletin_index');
        }

        return $this->render('bulletin/form.html.twig', [
            'bulletin' => $bulletin,
            'form'     => $form,
            'is_edit'  => false,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Bulletin $bulletin): Response
    {
        return $this->render('bulletin/show.html.twig', [
            'bulletin' => $bulletin,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bulletin $bulletin, EntityManagerInterface $em): Response
    {
        // Empêcher la modification si publié et non révoqué
        if ($bulletin->isPublished() && !$bulletin->isRevoked()) {
            $this->addFlash('error', 'Ce bulletin est publié et verrouillé. Il ne peut plus être modifié.');
            return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
        }

        $form = $this->createForm(BulletinType::class, $bulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bulletin->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            
            // Recalculer tous les rangs de la même période
            $this->bulletinRepository->recalculateAllRanks(
                $bulletin->getAcademicYear(),
                $bulletin->getSemester()
            );
            $em->flush();

            $this->auditService->log('Bulletin', $bulletin->getId(), 'UPDATED', $this->getUser());
            $this->addFlash('success', 'Bulletin modifié avec succès. Rangs recalculés.');

            return $this->redirectToRoute('admin_bulletin_index');
        }

        return $this->render('bulletin/form.html.twig', [
            'bulletin' => $bulletin,
            'form'     => $form,
            'is_edit'  => true,
        ]);
    }

    #[Route('/{id}/verify-status', name: 'verify_status', methods: ['POST'])]
    public function verifyStatus(Bulletin $bulletin, EntityManagerInterface $em): Response
    {
        try {
            $this->workflowService->verify($bulletin, $this->getUser());
            $em->flush();
            $this->addFlash('success', 'Bulletin passé en statut "Vérifié".');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
    }

    #[Route('/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validateBulletin(Bulletin $bulletin, EntityManagerInterface $em): Response
    {
        try {
            $this->workflowService->validate($bulletin, $this->getUser());
            $em->flush();
            $this->addFlash('success', 'Bulletin validé avec succès.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
    }

    #[Route('/{id}/publish', name: 'publish', methods: ['POST'])]
    public function publish(Bulletin $bulletin, EntityManagerInterface $em, Request $request): Response
    {
        try {
            $this->workflowService->publish($bulletin, $this->getUser());

            // Générer le PDF
            $baseUrl = $request->getSchemeAndHttpHost();
            $pdfPath = $this->pdfService->generateBulletinPdf($bulletin, $baseUrl);
            $bulletin->setPdfPath($pdfPath);

            $em->flush();
            
            $emailSent = false;
            $smsSent = false;
            
            // Envoyer automatiquement par email à l'étudiant
            try {
                $this->emailService->sendBulletinEmail($bulletin);
                $emailSent = true;
            } catch (\Exception $mailEx) {
                // Log silencieusement
            }
            
            // Envoyer automatiquement par SMS à l'étudiant
            try {
                if ($this->smsService->isConfigured()) {
                    $smsResult = $this->smsService->notifyBulletinReady($bulletin);
                    $smsSent = $smsResult['success'] ?? false;
                }
            } catch (\Exception $smsEx) {
                // Log silencieusement
            }
            
            // Message de confirmation adapté
            $message = 'Bulletin publié avec succès. PDF généré.';
            if ($emailSent && $smsSent) {
                $message .= ' Email et SMS envoyés à l\'étudiant.';
            } elseif ($emailSent) {
                $message .= ' Email envoyé à l\'étudiant.';
            } elseif ($smsSent) {
                $message .= ' SMS envoyé à l\'étudiant.';
            }
            
            $this->addFlash('success', $message);
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
    }

    #[Route('/{id}/pdf', name: 'pdf', methods: ['GET'])]
    public function downloadPdf(Bulletin $bulletin): Response
    {
        if (!$bulletin->getPdfPath()) {
            $this->addFlash('error', 'Aucun PDF disponible pour ce bulletin.');
            return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $bulletin->getPdfPath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier PDF est introuvable.');
            return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'bulletin_' . $bulletin->getId() . '.pdf'
        );

        return $response;
    }

    #[Route('/{id}/generate-pdf', name: 'generate_pdf', methods: ['POST'])]
    public function generatePdf(Bulletin $bulletin, Request $request, EntityManagerInterface $em): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $pdfPath = $this->pdfService->generateBulletinPdf($bulletin, $baseUrl);
        $bulletin->setPdfPath($pdfPath);
        $em->flush();

        // Envoyer automatiquement par email à l'étudiant
        try {
            $this->emailService->sendBulletinEmail($bulletin);
            $this->addFlash('success', 'PDF généré avec le template EduSmart et envoyé par email à l\'étudiant.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'PDF généré, mais l\'envoi d\'email a échoué: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_bulletin_index');
    }

    #[Route('/{id}/revoke', name: 'revoke', methods: ['POST'])]
    public function revoke(Request $request, Bulletin $bulletin, EntityManagerInterface $em): Response
    {
        $reason = $request->request->get('reason', 'Aucune raison spécifiée');

        try {
            $this->workflowService->revoke($bulletin, $this->getUser(), $reason);
            $em->flush();
            $this->addFlash('success', 'Bulletin révoqué.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Bulletin $bulletin, EntityManagerInterface $em): Response
    {
        if ($bulletin->isPublished()) {
            $this->addFlash('error', 'Impossible de supprimer un bulletin publié.');
            return $this->redirectToRoute('admin_bulletin_index');
        }

        if ($this->isCsrfTokenValid('delete'.$bulletin->getId(), $request->request->get('_token'))) {
            $this->auditService->log('Bulletin', $bulletin->getId(), 'DELETED', $this->getUser());
            $em->remove($bulletin);
            $em->flush();
            $this->addFlash('success', 'Bulletin supprimé.');
        }

        return $this->redirectToRoute('admin_bulletin_index');
    }

    #[Route('/{id}/send-sms', name: 'send_sms', methods: ['POST'])]
    public function sendSms(Bulletin $bulletin): Response
    {
        if (!$this->smsService->isConfigured()) {
            $this->addFlash('error', 'Service SMS non configuré. Vérifiez les clés Twilio dans .env');
            return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
        }

        $result = $this->smsService->notifyBulletinReady($bulletin);

        if ($result['success']) {
            $this->addFlash('success', 'SMS envoyé avec succès à l\'étudiant.');
        } else {
            $this->addFlash('error', 'Erreur envoi SMS: ' . ($result['error'] ?? 'Erreur inconnue'));
        }

        return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
    }

    #[Route('/{id}/send-email', name: 'send_email', methods: ['POST'])]
    public function sendEmail(Bulletin $bulletin): Response
    {
        try {
            $this->emailService->sendBulletinEmail($bulletin);
            $this->addFlash('success', 'Email envoyé avec succès à l\'étudiant.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur envoi email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
    }
}