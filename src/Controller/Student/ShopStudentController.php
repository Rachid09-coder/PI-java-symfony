<?php

namespace App\Controller\Student;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shop')]
class ShopStudentController extends AbstractController
{
    #[Route('/', name: 'student_shop_index')]
    public function index(): Response
    {
        $products = [
            ['id' => 1, 'name' => 'Livre de Mathématiques', 'price' => 25.00, 'image' => 'book.jpg'],
            ['id' => 2, 'name' => 'Calculatrice Scientifique', 'price' => 15.00, 'image' => 'calc.jpg'],
            ['id' => 3, 'name' => 'Cahier de notes EduSmart', 'price' => 5.00, 'image' => 'notebook.jpg'],
        ];

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
