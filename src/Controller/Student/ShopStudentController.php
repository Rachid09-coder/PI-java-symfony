<?php

namespace App\Controller\Student;

use App\Repository\ProductRepository; // Importation nécessaire
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shop')]
class ShopStudentController extends AbstractController
{
    #[Route('/', name: 'student_shop_index')]
    public function index(ProductRepository $productRepository): Response
    {
        // On récupère les vrais produits depuis la base de données
        $products = $productRepository->findAll();

        return $this->render('student/shop/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/cart', name: 'student_shop_cart')]
    public function cart(): Response
    {
        return $this->render('student/shop/cart.html.twig');
    }
}