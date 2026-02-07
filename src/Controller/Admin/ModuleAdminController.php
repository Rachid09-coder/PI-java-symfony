<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/modules')]
class ModuleAdminController extends AbstractController
{
    #[Route('/', name: 'admin_modules_manage')]
    public function index(): Response
    {
        $modules = [
            ['id' => 1, 'title' => 'Introduction aux Variables', 'course' => 'Programmation Python', 'order' => 1],
            ['id' => 2, 'title' => 'Les Sélecteurs CSS', 'course' => 'Développement Web', 'order' => 1],
            ['id' => 3, 'title' => 'Calcul Matriciel', 'course' => 'Algèbre Linéaire', 'order' => 1],
        ];

        return $this->render('admin/module/index.html.twig', [
            'modules' => $modules,
        ]);
    }

    #[Route('/new', name: 'admin_module_new')]
    #[Route('/{id}/edit', name: 'admin_module_edit')]
    public function form(?int $id = null): Response
    {
        return $this->render('admin/module/form.html.twig', [
            'id' => $id,
        ]);
    }
}
