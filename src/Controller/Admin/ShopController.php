<?php

namespace App\Controller\Admin;


use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/shop')]
class ShopController extends AbstractController
{


  
   

    // --- LA PARTIE CORRIGÉE ---
    #[Route('/categories', name: 'admin_shop_categories')]
    public function categories(
        Request $request, 
        EntityManagerInterface $entityManager, 
        CategoryRepository $categoryRepository
    ): Response {
        // 1. Création d'une nouvelle entité Category
        $category = new Category();
        
        // 2. Création du formulaire
        $form = $this->createForm(CategoryType::class, $category);
        
        // 3. Analyse de la requête (est-ce que l'utilisateur a cliqué sur "Créer")
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde en base de données
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a été ajoutée avec succès !');

            // Redirection pour éviter de renvoyer le formulaire en rafraîchissant la page
            return $this->redirectToRoute('admin_shop_categories');
        }

        // 4. On envoie le formulaire ET la liste réelle des catégories à la vue
        return $this->render('admin/shop/categories.html.twig', [
            'form' => $form->createView(),
            'categories' => $categoryRepository->findAll(),
        ]);
    }
    #[Route('/admin/shop')]


    #[Route('/', name: 'admin_shop_index')]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('admin/shop/index.html.twig', [
            'products' => $productRepository->findAll(),
            'categories' => $categoryRepository->findAll(), // Pour ton modal
        ]);
    }

    #[Route('/product/new', name: 'admin_shop_product_new')]
    #[Route('/product/{id}/edit', name: 'admin_shop_product_edit')]
    public function productForm(Request $request, EntityManagerInterface $em, Product $product = null): Response
    {
        if (!$product) {
            $product = new Product();
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit enregistré !');
            return $this->redirectToRoute('admin_shop_index');
        }

        return $this->render('admin/shop/product_form.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }

    #[Route('/product/{id}/delete', name: 'admin_shop_product_delete', methods: ['POST'])]
    public function deleteProduct(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $em->remove($product);
            $em->flush();
        }
        return $this->redirectToRoute('admin_shop_index');
    }
}