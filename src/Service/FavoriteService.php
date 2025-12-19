<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\FavoriteRepository;

class FavoriteService
{
    public function __construct(private FavoriteRepository $favoriteRepository) {}

    public function isFavoritedByUser(Product $product, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->favoriteRepository->findOneBy(['product' => $product, 'user' => $user]) !== null;
    }
}
