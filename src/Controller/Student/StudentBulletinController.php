<?php

namespace App\Controller\Student;

use App\Entity\Bulletin;
use App\Repository\BulletinRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/student/bulletins')]
class StudentBulletinController extends AbstractController
{
    #[Route('/', name: 'student_bulletins_index')]
    public function index(BulletinRepository $bulletinRepo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $bulletins = $bulletinRepo->findBy(
            ['student' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('student/bulletin/index.html.twig', [
            'bulletins' => $bulletins,
        ]);
    }

    /**
     * Télécharger le PDF d'un bulletin (uniquement pour son propre bulletin).
     */
    #[Route('/{id}/pdf', name: 'student_bulletin_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(Bulletin $bulletin): Response
    {
        $user = $this->getUser();
        if (!$user || $bulletin->getStudent() !== $user) {
            $this->addFlash('danger', 'Accès non autorisé.');
            return $this->redirectToRoute('student_bulletins_index');
        }

        if (!$bulletin->getPdfPath()) {
            $this->addFlash('warning', 'Aucun PDF disponible pour ce bulletin.');
            return $this->redirectToRoute('student_bulletins_index');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $bulletin->getPdfPath();
        if (!is_file($filePath)) {
            $this->addFlash('warning', 'Le fichier PDF est introuvable.');
            return $this->redirectToRoute('student_bulletins_index');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'bulletin_' . $bulletin->getAcademicYear() . '_' . $bulletin->getSemester() . '.pdf'
        );
        return $response;
    }
}
