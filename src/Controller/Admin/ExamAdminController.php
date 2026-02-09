<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Exam;
use App\Form\ExamType;
use App\Repository\ExamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

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
    public function examForm(Request $request, EntityManagerInterface $entityManager, Exam $exam = null): Response
    {
        if (!$exam) {
            $exam = new Exam();
        }

        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($exam);
            $entityManager->flush();

            $this->addFlash('success', 'Examen enregistré avec succès !');

            return $this->redirectToRoute('admin_exams_manage');
        }

        return $this->render('admin/exam/form.html.twig', [
            'form' => $form->createView(),
            'exam' => $exam,
            'isEdit' => $exam->getId() !== null
        ]);
    }

    #[Route('/{id}/questions', name: 'admin_exam_questions')]
    public function manageQuestions(Exam $exam): Response
    {
        return $this->render('admin/exam/questions.html.twig', [
            'exam' => $exam
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_exam_delete', methods: ['POST'])]
    public function delete(Request $request, Exam $exam, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exam->getId(), $request->request->get('_token'))) {
            $entityManager->remove($exam);
            $entityManager->flush();
            $this->addFlash('success', 'Examen supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_exams_manage');
    }
}
