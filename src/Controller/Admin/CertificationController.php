<?php

namespace App\Controller\Admin;

use App\Entity\Certification;
use App\Form\CertificationType;
use App\Repository\CertificationRepository;
use App\Service\AuditService;
use App\Service\HmacService;
use App\Service\PdfGeneratorService;
use App\Service\VerificationCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
    ) {
    }

    #[Route('/', name: 'admin_certification_index')]
    public function index(CertificationRepository $certificationRepo): Response 
    {
        return $this->render('admin/certification/index.html.twig', [
            'certifications' => $certificationRepo->findBy([], ['issuedAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_certification_new')]
    public function certificationNew(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $certification = new Certification();
        $form = $this->createForm(CertificationType::class, $certification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            $this->auditService->log('Certification', $certification->getId(), 'CREATED', $this->getUser(), [
                'type' => $certification->getType(),
                'unique_number' => $certification->getUniqueNumber(),
            ]);

            $this->addFlash('success', 'Certification créée avec succès. N° : ' . $certification->getUniqueNumber());
            return $this->redirectToRoute('admin_certification_index');
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
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            $this->auditService->log('Certification', $certification->getId(), 'UPDATED', $this->getUser());

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

        $this->auditService->log('Certification', $certification->getId(), 'PDF_GENERATED', $this->getUser());
        $this->addFlash('success', 'PDF de la certification généré avec succès.');

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

        $this->auditService->log('Certification', $certification->getId(), 'REVOKED', $this->getUser(), [
            'reason' => $reason,
        ]);

        $this->addFlash('success', 'Certification révoquée.');
        return $this->redirectToRoute('admin_certification_index');
    }

    #[Route('/{id}/delete', name: 'admin_certification_delete', methods: ['POST'])]
    public function certificationDelete(Certification $certification, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_cert_' . $certification->getId(), $request->request->get('_token'))) {
            $this->auditService->log('Certification', $certification->getId(), 'DELETED', $this->getUser());
            $em->remove($certification);
            $em->flush();
            $this->addFlash('success', 'Certification supprimée.');
        }

        return $this->redirectToRoute('admin_certification_index');
    }
}
