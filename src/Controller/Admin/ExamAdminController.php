<?php

namespace App\Controller\Admin;

use App\Entity\Exam;
use App\Form\ExamType;
use App\Repository\ExamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/exam')]
class ExamAdminController extends AbstractController
{
    #[Route('/manage', name: 'admin_exams_manage')]
    public function manage(ExamRepository $examRepository): Response
    {
        return $this->render('admin/exam/manage.html.twig', [
            'exams' => $examRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_exam_new')]
    #[Route('/{id}/edit', name: 'admin_exam_edit')]
    public function examForm(Request $request, EntityManagerInterface $em, ?Exam $exam = null): Response
    {
        if (!$exam) {
            $exam = new Exam();
        }

        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('filePath')->getData();

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/exams',
                        $newFilename
                    );
                    $exam->setFilePath('uploads/exams/'.$newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier');
                }
            }

            $em->persist($exam);
            $em->flush();

            $this->addFlash('success', 'Examen enregistré avec succès !');

            return $this->redirectToRoute('admin_exams_manage');
        }

        return $this->render('admin/exam/form.html.twig', [
            'form' => $form->createView(),
            'exam' => $exam,
            'isEdit' => $exam->getId() !== null
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_exam_delete', methods: ['POST'])]
    public function delete(Request $request, Exam $exam, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exam->getId(), $request->request->get('_token'))) {
            $em->remove($exam);
            $em->flush();
            $this->addFlash('success', 'Examen supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_exams_manage');
    }

    #[Route('/{id}/questions', name: 'admin_exam_questions')]
    public function manageQuestions(Exam $exam): Response
    {
        return $this->render('admin/exam/questions.html.twig', ['exam' => $exam]);
    }
}
