<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/category')]
final class CategoryController extends AbstractController
{
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/shop/categories.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }
#[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request, 
    Category $category, 
    EntityManagerInterface $entityManager, 
    CategoryRepository $categoryRepository // Ajoute le repository ici
): Response {
    $form = $this->createForm(CategoryType::class, $category);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        
        $this->addFlash('success', 'Catégorie mise à jour !');
        return $this->redirectToRoute('admin_shop_categories');
    }

    // On utilise TON template au lieu de 'category/edit.html.twig'
    return $this->render('admin/shop/categories.html.twig', [
        'category' => $category,
        'form' => $form->createView(),
        'categories' => $categoryRepository->findAll(), // Pour afficher la liste à droite
        'is_edit' => true // Petit indicateur utile
    ]);
}
    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
        // En supprimant la catégorie, Doctrine va automatiquement supprimer 
        // tous les produits liés grâce au cascade=['remove']
        $entityManager->remove($category);
        $entityManager->flush();
        
        $this->addFlash('success', 'La catégorie et tous ses produits ont été supprimés.');
    }

    return $this->redirectToRoute('admin_shop_categories');
}
}
