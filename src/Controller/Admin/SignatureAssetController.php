<?php

namespace App\Controller\Admin;

use App\Entity\SignatureAsset;
use App\Form\SignatureAssetType;
use App\Repository\SignatureAssetRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/signature-asset')]
class SignatureAssetController extends AbstractController
{
    #[Route('/', name: 'admin_signature_asset_index')]
    public function index(SignatureAssetRepository $repo): Response
    {
        return $this->render('admin/signature_asset/index.html.twig', [
            'assets' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_signature_asset_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, AuditService $audit): Response
    {
        $asset = new SignatureAsset();
        $form = $this->createForm(SignatureAssetType::class, $asset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/signatures',
                        $newFilename
                    );
                    $asset->setImagePath('uploads/signatures/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload.');
                    return $this->redirectToRoute('admin_signature_asset_index');
                }
            }

            $asset->setUploadedBy($this->getUser());
            $em->persist($asset);
            $em->flush();

            $audit->log('SignatureAsset', $asset->getId(), 'CREATED', $this->getUser());
            $this->addFlash('success', 'Image ajoutée avec succès.');
            return $this->redirectToRoute('admin_signature_asset_index');
        }

        return $this->render('admin/signature_asset/form.html.twig', [
            'form' => $form,
            'asset' => $asset,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_signature_asset_delete', methods: ['POST'])]
    public function delete(SignatureAsset $asset, Request $request, EntityManagerInterface $em, AuditService $audit): Response
    {
        if ($this->isCsrfTokenValid('delete_asset_' . $asset->getId(), $request->request->get('_token'))) {
            // Supprimer le fichier physique
            $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $asset->getImagePath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $audit->log('SignatureAsset', $asset->getId(), 'DELETED', $this->getUser());
            $em->remove($asset);
            $em->flush();
            $this->addFlash('success', 'Image supprimée.');
        }

        return $this->redirectToRoute('admin_signature_asset_index');
    }
}
