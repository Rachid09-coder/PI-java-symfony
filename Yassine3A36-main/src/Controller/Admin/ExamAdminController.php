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
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/exam')]
class ExamAdminController extends AbstractController
{
    #[Route('/manage', name: 'admin_exams_manage')]
    public function manage(ExamRepository $repo): Response
    {
        return $this->render('admin/exam/manage.html.twig', [
            'exams' => $repo->findAll()
        ]);
    }

    #[Route('/new', name: 'admin_exam_new')]
    #[Route('/{id}/edit', name: 'admin_exam_edit')]
    public function form(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ?Exam $exam = null
    ): Response {

        if(!$exam){
            $exam = new Exam();
        }

        $form = $this->createForm(ExamType::class,$exam);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $file = $form->get('filePath')->getData();

            if($file){
                $filename = pathinfo($file->getClientOriginalName(),PATHINFO_FILENAME);
                $safe = $slugger->slug($filename);
                $new = $safe.'-'.uniqid().'.'.$file->guessExtension();
                $file->move($this->getParameter('exams_directory'),$new);
                $exam->setFilePath($new);
            }

            $em->persist($exam);
            $em->flush();

            return $this->redirectToRoute('admin_exams_manage');
        }

        return $this->render('admin/exam/form.html.twig',[
            'form'=>$form->createView()
        ]);
    }
}
