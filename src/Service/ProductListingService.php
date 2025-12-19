<?php

namespace App\Service;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Service\FavoriteService;
use App\Entity\User;

class ProductListingService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private FavoriteService $favoriteService
    ) {}

    public function getProductsWithFavorites(?User $user = null): array
    {
        $products = $this->productRepository->findAll();
        return $this->addFavoriteStatus($products, $user);
    }

    public function getProductsByCategoryWithFavorites(int $categoryId, ?User $user = null): array
    {
        $products = $this->productRepository->findByCategoryWithImages($categoryId);
        return $this->addFavoriteStatus($products, $user);
    }

    public function getProductsByCategoryNameWithFavorites(string $categoryName, ?User $user = null): array
    {
        $category = $this->categoryRepository->findOneBy(['name' => $categoryName]);
        if (!$category) {
            return [];
        }

        return $this->getProductsByCategoryWithFavorites($category->getId(), $user);
    }

    private function addFavoriteStatus(array $products, ?User $user): array
    {
        $productData = [];
        foreach ($products as $product) {
            $productData[] = [
                'product' => $product,
                'isFavorited' => $this->favoriteService->isFavoritedByUser($product, $user)
            ];
        }
        return $productData;
    }
}
