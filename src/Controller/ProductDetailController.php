<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Service\CartService;
use App\Service\FavoriteService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductDetailController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private CartService $cartService,
        private FavoriteService $favoriteService
    ) {}

    #[Route('/product', name: 'app_product')]
    public function index(): Response
    {
        // Kadın kategorisini bul (varsayılan kategori)
        $category = $this->categoryRepository->findOneBy(['name' => 'Kadın']);

        $productData = [];
        if ($category) {
            $products = $this->productRepository->findByCategoryWithImages($category->getId());
            $user = $this->getUser();
            foreach ($products as $product) {
                $productData[] = [
                    'product' => $product,
                    'isFavorited' => $this->favoriteService->isFavoritedByUser($product, $user)
                ];
            }
        }

        $cartCount = $this->cartService->getCartCountForUser($this->getUser());

        return $this->render('product/index.html.twig', [
            'productData' => $productData,
            'category' => $category,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/category/{id}', name: 'app_category_show')]
    public function showByCategory(int $id): Response
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Kategori bulunamadı.');
        }

        $products = $this->productRepository->findByCategoryWithImages($id);

        $productData = [];
        $user = $this->getUser();
        foreach ($products as $product) {
            $productData[] = [
                'product' => $product,
                'isFavorited' => $this->favoriteService->isFavoritedByUser($product, $user)
            ];
        }

        $cartCount = $this->cartService->getCartCountForUser($this->getUser());

        return $this->render('product/index.html.twig', [
            'productData' => $productData,
            'category' => $category,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show')]
    public function show(int $id): Response
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Ürün bulunamadı.');
        }

        $cartCount = $this->cartService->getCartCountForUser($this->getUser());
        $isFavorited = $this->favoriteService->isFavoritedByUser($product, $this->getUser());

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'cartCount' => $cartCount,
            'isFavorited' => $isFavorited,
        ]);
    }
}
