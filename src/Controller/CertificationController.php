<?php

namespace App\Controller;

use App\Entity\Certification;
use App\Form\CertificationType;
use App\Repository\BulletinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/certification')]
class CertificationController extends AbstractController
{
    #[Route('/api/bulletin/{studentId}', name: 'app_certification_get_bulletins', methods: ['GET'])]
    public function getBulletins(
        int $studentId,
        BulletinRepository $bulletinRepository
    ): JsonResponse {

        $bulletins = $bulletinRepository->findByStudentId($studentId);

        $data = [];

        foreach ($bulletins as $bulletin) {
            $data[] = [
                'id' => $bulletin->getId(),
                'label' => $bulletin->getAcademicYear() . ' - ' . $bulletin->getSemester(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/new', name: 'app_certification_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $certification = new Certification();
        $form = $this->createForm(CertificationType::class, $certification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($certification);
            $em->flush();

            return $this->redirectToRoute('app_certification_index');
        }

        return $this->render('certification/new.html.twig', [
            'certification' => $certification,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_certification_show', methods: ['GET'])]
    public function show(Certification $certification): Response
    {
        return $this->render('certification/show.html.twig', [
            'certification' => $certification,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_certification_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Certification $certification, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CertificationType::class, $certification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_certification_index');
        }

        return $this->render('certification/edit.html.twig', [
            'certification' => $certification,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_certification_delete', methods: ['POST'])]
    public function delete(Request $request, Certification $certification, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$certification->getId(), $request->request->get('_token'))) {
            $em->remove($certification);
            $em->flush();
        }

        return $this->redirectToRoute('app_certification_index');
    }
}
