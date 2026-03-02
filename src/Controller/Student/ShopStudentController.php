<?php

namespace App\Controller\Student;

use App\Entity\Product;
use App\Entity\WishlistItem;
use App\Repository\ProductRepository;
use App\Repository\WishlistItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/shop')]
class ShopStudentController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private WishlistItemRepository $wishlistRepository,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/', name: 'student_shop_index')]
    public function index(): Response
    {
        $products = $this->productRepository->findBy([], ['id' => 'DESC']);
        $wishlistIds = [];
        $recommended = [];
        if ($this->getUser()) {
            $wishlistIds = $this->wishlistRepository->getProductIdsByUser($this->getUser());
            $recommended = $this->productRepository->findRecommendedForShop(6, $wishlistIds);
        } else {
            $recommended = $this->productRepository->findRecommendedForShop(6, []);
        }

        return $this->render('student/shop/index.html.twig', [
            'products' => $products,
            'wishlistProductIds' => $wishlistIds,
            'recommendedProducts' => $recommended,
        ]);
    }

    #[Route('/cart', name: 'student_shop_cart')]
    public function cart(): Response
    {
        $wishlistItems = [];
        $recommended = [];
        if ($this->getUser()) {
            $wishlistItems = $this->wishlistRepository->findByUser($this->getUser());
            $recommended = $this->productRepository->findRecommendedForCart(4);
        } else {
            $recommended = $this->productRepository->findRecommendedForCart(4);
        }

        return $this->render('student/shop/cart.html.twig', [
            'wishlistItems' => $wishlistItems,
            'recommendedProducts' => $recommended,
        ]);
    }

    #[Route('/wishlist', name: 'student_shop_wishlist', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function wishlist(): Response
    {
        $items = $this->wishlistRepository->findByUser($this->getUser());

        return $this->render('student/shop/wishlist.html.twig', [
            'wishlistItems' => $items,
        ]);
    }

    #[Route('/wishlist/toggle/{id}', name: 'student_shop_wishlist_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleWishlist(Product $product, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $item = $this->wishlistRepository->findOneByUserAndProduct($user, $product);

        if ($item) {
            $this->em->remove($item);
            $this->em->flush();
            return $this->json(['inWishlist' => false]);
        }

        $item = new WishlistItem();
        $item->setUser($user);
        $item->setProduct($product);
        $this->em->persist($item);
        $this->em->flush();
        return $this->json(['inWishlist' => true]);
    }
}
