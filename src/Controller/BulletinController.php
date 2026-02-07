<?php

namespace App\Controller;

use App\Entity\Bulletin;
use App\Form\BulletinType;
use App\Repository\BulletinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/bulletin', name: 'admin_bulletin_')]
class BulletinController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BulletinRepository $bulletinRepository): Response
    {
        return $this->render('bulletin/index.html.twig', [
            'bulletins' => $bulletinRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $bulletin = new Bulletin();
        $form = $this->createForm(BulletinType::class, $bulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($bulletin);
            $entityManager->flush();

            return $this->redirectToRoute('admin_bulletin_index');
        }

        return $this->render('bulletin/new.html.twig', [
            'bulletin' => $bulletin,
            'form' => $form,
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
    public function edit(Request $request, Bulletin $bulletin, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BulletinType::class, $bulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_bulletin_index');
        }

        return $this->render('bulletin/edit.html.twig', [
            'bulletin' => $bulletin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Bulletin $bulletin, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bulletin->getId(), $request->request->get('_token'))) {
            $entityManager->remove($bulletin);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_bulletin_index');
    }
}
