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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/shop')]
class ShopController extends AbstractController
{
    #[Route('/', name: 'admin_shop_index')]
    public function index(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $sortBy = $request->query->get('sortBy', 'id');
        $direction = $request->query->get('direction', 'ASC');
        
        $filters = [
            'category' => $request->query->get('category'),
            'search' => $request->query->get('search'),
        ];

        $products = $productRepository->findByFilters($filters, $sortBy, $direction);

        return $this->render('admin/shop/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'currentSort' => $sortBy,
            'currentDirection' => $direction,
            'currentFilters' => $filters,
        ]);
    }

    #[Route('/categories', name: 'admin_shop_categories')]
    public function categories(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response 
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', 'La catégorie a été ajoutée avec succès !');
            return $this->redirectToRoute('admin_shop_categories');
        }

        $sortBy = $request->query->get('sortBy', 'id');
        $direction = $request->query->get('direction', 'ASC');
        $filters = ['search' => $request->query->get('search')];

        $categories = $categoryRepository->findByFilters($filters, $sortBy, $direction);

        return $this->render('admin/shop/categories.html.twig', [
            'form' => $form->createView(),
            'categories' => $categories,
            'currentSort' => $sortBy,
            'currentDirection' => $direction,
            'currentFilters' => $filters,
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
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            // SI UNE IMAGE EST POSTÉE
            if ($imageFile) {
                // On crée un nom unique (ex: 65c8f...jpg)
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                try {
                    // On déplace physiquement le fichier
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/produit',
                        $newFilename
                    );
                    
                    // ON FORCE LE NOM DANS L'ENTITÉ (C'est ça qui enlève le NULL)
                    $product->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', "Erreur lors de l'upload");
                }
            }

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
