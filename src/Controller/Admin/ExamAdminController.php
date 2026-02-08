<?php

namespace App\Controller\Admin;

use App\Entity\Exam;
use App\Form\ExamType;
use App\Repository\ExamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/exam')]
class ExamAdminController extends AbstractController
{

    // =========================
    // LIST ALL EXAMS
    // =========================
    #[Route('/', name: 'admin_exam_manage')]
    public function manage(ExamRepository $examRepository): Response
    {
        $exams = $examRepository->findBy([], ['id' => 'DESC']);

        return $this->render('admin/exam/manage.html.twig', [
            'exams' => $exams,
        ]);
    }


    // =========================
    // CREATE NEW EXAM
    // =========================
  #[Route('/new', name: 'admin_exam_new')]
public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
{
    $exam = new Exam();

    $form = $this->createForm(ExamType::class, $exam);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $form->get('filePath')->getData();

        if ($uploadedFile) {

            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

            try {
                $uploadedFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/exams',
                    $newFilename
                );
            } catch (FileException $e) {
                dd($e);
            }

            // SAVE FILE NAME IN DATABASE
            $exam->setFilePath($newFilename);
        }

        $em->persist($exam);
        $em->flush();

        $this->addFlash('success', 'Examen créé avec succès');

        return $this->redirectToRoute('admin_exam_manage');
    }

    return $this->render('admin/exam/form.html.twig', [
        'form' => $form->createView(),
    ]);
}
// =========================
// EDIT EXAM
// =========================
#[Route('/edit/{id}', name: 'admin_exam_edit')]
public function edit(Exam $exam, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
{
    $form = $this->createForm(ExamType::class, $exam);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $form->get('filePath')->getData();

        if ($uploadedFile) {

            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

            $uploadedFile->move(
                $this->getParameter('kernel.project_dir').'/public/uploads/exams',
                $newFilename
            );

            $exam->setFilePath($newFilename);
        }

        $em->flush();

        $this->addFlash('success', 'Examen modifié avec succès');
        return $this->redirectToRoute('admin_exam_manage');
    }

    return $this->render('admin/exam/form.html.twig', [
        'form' => $form->createView(),
        'exam' => $exam
    ]);
}


    // =========================
    // DELETE EXAM
    // =========================
    #[Route('/delete/{id}', name: 'admin_exam_delete')]
    public function delete(Exam $exam, EntityManagerInterface $em): Response
    {
        $em->remove($exam);
        $em->flush();

        $this->addFlash('danger', 'Examen supprimé');

        return $this->redirectToRoute('admin_exam_manage');
    }

}
