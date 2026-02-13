<?php

namespace App\Controller;

use App\Entity\Bulletin;
use App\Entity\ReportCardLine;
use App\Form\BulletinType;
use App\Repository\BulletinRepository;
use App\Service\AuditService;
use App\Service\BulletinWorkflowService;
use App\Service\PdfGeneratorService;
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
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BulletinRepository $bulletinRepository): Response
    {
        return $this->render('bulletin/index.html.twig', [
            'bulletins' => $bulletinRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $bulletin = new Bulletin();
        $form = $this->createForm(BulletinType::class, $bulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($bulletin);
            $em->flush();

            $this->auditService->log('Bulletin', $bulletin->getId(), 'CREATED', $this->getUser());
            $this->addFlash('success', 'Bulletin créé avec succès.');

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

            $this->auditService->log('Bulletin', $bulletin->getId(), 'UPDATED', $this->getUser());
            $this->addFlash('success', 'Bulletin modifié avec succès.');

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
            $this->addFlash('success', 'Bulletin publié avec succès. PDF généré.');
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
}