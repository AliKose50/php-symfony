<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SporController extends AbstractController
{
    #[Route('/spor', name: 'app_spor')]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository, CartRepository $cartRepository): Response
    {
        // Erkek kategorisini bul
        $category = $categoryRepository->findOneBy(['name' => 'Erkek']);

        if ($category) {
            $products = $productRepository->findByCategoryWithImages($category->getId());
        } else {
            $products = $productRepository->findAll();
        }

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cart = null;
        $cartCount = 0;
        if ($this->getUser()) {
            $cart = $cartRepository->findOneBy(['full_name' => $this->getUser()]);
            if ($cart) {
                foreach ($cart->getCartItems() as $item) {
                    $cartCount += (int) $item->getQuantity();
                }
            }
        }

        return $this->render('spor/index.html.twig', [
            'products' => $products,
            'category' => $category,
            'cart' => $cart,
            'cartCount' => $cartCount,
        ]);
    }
}
