<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository, CartRepository $cartRepository): Response
    {
        $products = $productRepository->findAll();

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

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'cart' => $cart,
            'cartCount' => $cartCount,
        ]);
    }
}
