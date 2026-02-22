<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/shop')]
class ShopController extends AbstractController
{
    #[Route('/', name: 'admin_shop_index')]
    public function index(): Response
    {
        $categories = ['Livres', 'Équipements', 'Logiciels', 'Accessoires'];
        $products = [
            ['id' => 1, 'name' => 'Livre de Mathématiques', 'category' => 'Livres', 'price' => 25.00, 'stock' => 50],
            ['id' => 2, 'name' => 'Calculatrice Scientifique', 'category' => 'Équipements', 'price' => 15.00, 'stock' => 20],
        ];

        return $this->render('admin/shop/index.html.twig', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    #[Route('/product/new', name: 'admin_shop_product_new')]
    #[Route('/product/{id}/edit', name: 'admin_shop_product_edit')]
    public function productForm(?int $id = null): Response
    {
        $product = $id ? ['id' => $id, 'name' => 'Calculatrice Scientifique', 'price' => 15.0, 'stock' => 20] : null;
        return $this->render('admin/shop/product_form.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/categories', name: 'admin_shop_categories')]
    public function categories(): Response
    {
        return $this->render('admin/shop/categories.html.twig');
    }
}
