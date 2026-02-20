<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\ForumPost;
use App\Entity\ForumThread;
use App\Form\ForumPostType;
use App\Form\ForumThreadType;
use App\Repository\ForumThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/forum')]
#[IsGranted('ROLE_USER')]
class ForumController extends AbstractController
{
    #[Route('/course/{id}', name: 'forum_index', methods: ['GET'])]
    public function index(Course $course, ForumThreadRepository $threadRepo): Response
    {
        $threads = $threadRepo->findByCourseOrderByUpdated($course);

        return $this->render('forum/index.html.twig', [
            'course' => $course,
            'threads' => $threads,
        ]);
    }

    #[Route('/course/{id}/new', name: 'forum_new_thread', methods: ['GET', 'POST'])]
    public function newThread(Course $course, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ForumThreadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $this->getUser();

            $thread = new ForumThread();
            $thread->setCourse($course);
            $thread->setAuthor($user);
            $thread->setTitle($data['title']);

            $post = new ForumPost();
            $post->setThread($thread);
            $post->setAuthor($user);
            $post->setContent($data['content']);

            $thread->addPost($post);
            $em->persist($thread);
            $em->flush();

            $this->addFlash('success', 'Sujet créé avec succès.');
            return $this->redirectToRoute('forum_thread', ['id' => $course->getId(), 'threadId' => $thread->getId()]);
        }

        return $this->render('forum/new_thread.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/course/{id}/thread/{threadId}', name: 'forum_thread', methods: ['GET', 'POST'])]
    public function thread(Course $course, int $threadId, Request $request, EntityManagerInterface $em, ForumThreadRepository $threadRepo): Response
    {
        $thread = $threadRepo->findOneBy(['id' => $threadId, 'course' => $course]);
        if (!$thread) {
            throw $this->createNotFoundException('Sujet introuvable.');
        }

        $post = new ForumPost();
        $post->setThread($thread);
        $form = $this->createForm(ForumPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAuthor($this->getUser());
            $thread->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Réponse publiée.');
            return $this->redirectToRoute('forum_thread', ['id' => $course->getId(), 'threadId' => $thread->getId()]);
        }

        return $this->render('forum/thread.html.twig', [
            'course' => $course,
            'thread' => $thread,
            'form' => $form,
        ]);
    }
}
