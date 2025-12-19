<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Service\CartService;
use App\Service\FavoriteService;
use Symfony\Component\Routing\Attribute\Route;

final class SporController extends AbstractController
{
    #[Route('/spor', name: 'app_spor')]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository, CartService $cartService, FavoriteService $favoriteService): Response
    {
        // Erkek kategorisini bul
        $category = $categoryRepository->findOneBy(['name' => 'Erkek']);

        if ($category) {
            $products = $productRepository->findByCategoryWithImages($category->getId());
        } else {
            $products = $productRepository->findAll();
        }

        // Favori durumlarını ekle
        $productData = [];
        $user = $this->getUser();
        foreach ($products as $product) {
            $productData[] = [
                'product' => $product,
                'isFavorited' => $favoriteService->isFavoritedByUser($product, $user)
            ];
        }

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = $cartService->getCartCountForUser($this->getUser());

        return $this->render('spor/index.html.twig', [
            'products' => $productData,
            'category' => $category,
            'cartCount' => $cartCount,
        ]);
    }
}
