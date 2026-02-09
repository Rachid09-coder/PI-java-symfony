<?php

namespace App\Controller\Admin;


use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;

#[Route('/admin/users')]
class UserAdminController extends AbstractController
{
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // ✅ optionnel: limiter l'edit aux étudiants
        // if ($user->getRole() !== 'etudiant') {
        //     throw $this->createNotFoundException();
        // }

        $form = $this->createForm(UserType::class, $user);

        // ✅ on ne change pas le mot de passe ici
        if ($form->has('password')) {
            $form->remove('password');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Modifications enregistrées.');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/user/form.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST', 'GET'])]
    public function delete(int $id): Response
    {
        // Logique de suppression simulée
        return $this->redirectToRoute('admin_dashboard');
    }
}
