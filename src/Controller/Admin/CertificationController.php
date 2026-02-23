<?php

namespace App\Controller\Admin;

use App\Entity\Bulletin;
use App\Entity\Certification;
use App\Form\BulletinType;
use App\Form\CertificationType;
use App\Repository\BulletinRepository;
use App\Repository\CertificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/certification')]
class CertificationController extends AbstractController
{
    #[Route('/', name: 'admin_certification_index')]
    public function index(
        CertificationRepository $certificationRepo,
        BulletinRepository $bulletinRepo
    ): Response {
        return $this->render('admin/certification/index.html.twig', [
            'certifications' => $certificationRepo->findBy([], ['issuedAt' => 'DESC']),
            'bulletins' => $bulletinRepo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    // ========== BULLETIN CRUD ==========
    
    #[Route('/bulletin/new', name: 'admin_bulletin_new')]
    public function bulletinNew(Request $request, EntityManagerInterface $em): Response
    {
        $bulletin = new Bulletin();
        $form = $this->createForm(BulletinType::class, $bulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($bulletin);
            $em->flush();

            $this->addFlash('success', 'Bulletin créé avec succès.');
            return $this->redirectToRoute('admin_certification_index');
        }

        return $this->render('admin/certification/bulletin_form.html.twig', [
            'form' => $form,
            'bulletin' => $bulletin,
        ]);
    }

    #[Route('/bulletin/{id}/edit', name: 'admin_bulletin_edit')]
    public function bulletinEdit(Bulletin $bulletin, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(BulletinType::class, $bulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bulletin->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Bulletin modifié avec succès.');
            return $this->redirectToRoute('admin_certification_index');
        }

        return $this->render('admin/certification/bulletin_form.html.twig', [
            'form' => $form,
            'bulletin' => $bulletin,
        ]);
    }

    #[Route('/bulletin/{id}/delete', name: 'admin_bulletin_delete', methods: ['POST'])]
    public function bulletinDelete(Bulletin $bulletin, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_bulletin_' . $bulletin->getId(), $request->request->get('_token'))) {
            $em->remove($bulletin);
            $em->flush();
            $this->addFlash('success', 'Bulletin supprimé.');
        }

        return $this->redirectToRoute('admin_certification_index');
    }

    // ========== CERTIFICATION CRUD ==========
    
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
            // Generate verification code if not provided
            if (!$certification->getVerificationCode()) {
                $certification->setVerificationCode(strtoupper(bin2hex(random_bytes(6))));
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

            $em->persist($certification);
            $em->flush();

            $this->addFlash('success', 'Certification créée avec succès.');
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

            $em->flush();

            $this->addFlash('success', 'Certification modifiée avec succès.');
            return $this->redirectToRoute('admin_certification_index');
        }

        return $this->render('admin/certification/cert_form.html.twig', [
            'form' => $form,
            'certification' => $certification,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_certification_delete', methods: ['POST'])]
    public function certificationDelete(Certification $certification, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_cert_' . $certification->getId(), $request->request->get('_token'))) {
            $em->remove($certification);
            $em->flush();
            $this->addFlash('success', 'Certification supprimée.');
        }

        return $this->redirectToRoute('admin_certification_index');
    }
}
