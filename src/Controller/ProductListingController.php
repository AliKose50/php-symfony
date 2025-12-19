<?php

namespace App\Controller;

use App\Service\ProductListingService;
use App\Service\CartService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductListingController extends AbstractController
{
    public function __construct(
        private ProductListingService $productListingService,
        private CartService $cartService
    ) {}

    #[Route('/urunler', name: 'app_all_products')]
    public function allProducts(): Response
    {
        $productData = $this->productListingService->getProductsWithFavorites($this->getUser());
        $cartCount = $this->cartService->getCartCountForUser($this->getUser());

        return $this->render('product/all_products.html.twig', [
            'productData' => $productData,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/fashion', name: 'app_fashion')]
    public function fashion(): Response
    {
        $productData = $this->productListingService->getProductsByCategoryNameWithFavorites('Kadın', $this->getUser());
        $cartCount = $this->cartService->getCartCountForUser($this->getUser());

        return $this->render('product/fashion.html.twig', [
            'productData' => $productData,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/sports', name: 'app_sports')]
    public function sports(): Response
    {
        $productData = $this->productListingService->getProductsByCategoryNameWithFavorites('Erkek', $this->getUser());
        $cartCount = $this->cartService->getCartCountForUser($this->getUser());

        return $this->render('product/sports.html.twig', [
            'productData' => $productData,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/electronics', name: 'app_electronics')]
    public function electronics(): Response
    {
        // Tüm kategorilerden elektronik ürünleri çek (şimdilik tüm ürünleri çekiyoruz)
        $productData = $this->productListingService->getProductsWithFavorites($this->getUser());
        $cartCount = $this->cartService->getCartCountForUser($this->getUser());

        return $this->render('product/electronics.html.twig', [
            'productData' => $productData,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/home-living', name: 'app_home_living')]
    public function homeLiving(): Response
    {
        // Tüm kategorilerden ev ürünleri çek (şimdilik tüm ürünleri çekiyoruz)
        $productData = $this->productListingService->getProductsWithFavorites($this->getUser());
        $cartCount = $this->cartService->getCartCountForUser($this->getUser());

        return $this->render('product/home_living.html.twig', [
            'productData' => $productData,
            'cartCount' => $cartCount,
        ]);
    }
}
