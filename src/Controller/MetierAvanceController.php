<?php

namespace App\Controller;

use App\Entity\MetierAvance;
use App\Form\MetierAvanceType;
use App\Repository\MetierAvanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/metier-avance')]
class MetierAvanceController extends AbstractController
{
    #[Route('/', name: 'app_metier_avance_index', methods: ['GET'])]
    public function index(MetierAvanceRepository $metierAvanceRepository): Response
    {
        return $this->render('metier_avance/index.html.twig', [
            'metier_avances' => $metierAvanceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_metier_avance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $metierAvance = new MetierAvance();
        $form = $this->createForm(MetierAvanceType::class, $metierAvance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($metierAvance);
            $em->flush();

            return $this->redirectToRoute('app_metier_avance_index');
        }

        return $this->render('metier_avance/new.html.twig', [
            'metier_avance' => $metierAvance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_metier_avance_show', methods: ['GET'])]
    public function show(MetierAvance $metierAvance): Response
    {
        return $this->render('metier_avance/show.html.twig', [
            'metier_avance' => $metierAvance,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_metier_avance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MetierAvance $metierAvance, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MetierAvanceType::class, $metierAvance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_metier_avance_index');
        }

        return $this->render('metier_avance/edit.html.twig', [
            'metier_avance' => $metierAvance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_metier_avance_delete', methods: ['POST'])]
    public function delete(Request $request, MetierAvance $metierAvance, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$metierAvance->getId(), $request->request->get('_token'))) {
            $em->remove($metierAvance);
            $em->flush();
        }

        return $this->redirectToRoute('app_metier_avance_index');
    }
}
