<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\FavoriteRepository;
use App\Entity\Favorite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FavoriteController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private FavoriteRepository $favoriteRepository,
        private EntityManagerInterface $em,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    #[Route('/favorite/toggle/{productId}', name: 'app_favorite_toggle', methods: ['POST'])]
    public function toggleFavorite(Request $request, int $productId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Oturum açmanız gerekiyor'
            ], 401);
        }

        // CSRF token kontrolü
        $submittedToken = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('submit', $submittedToken))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Geçersiz istek'
            ], 400);
        }

        $product = $this->productRepository->find($productId);
        if (!$product) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ürün bulunamadı'
            ], 404);
        }

        // Mevcut favori kontrolü
        $favorite = $this->favoriteRepository->findOneBy([
            'user' => $user,
            'product' => $product
        ]);

        if ($favorite) {
            // Favoriden çıkar
            $this->em->remove($favorite);
            $this->em->flush();
            return new JsonResponse([
                'success' => true,
                'message' => 'Ürün favorilerden çıkarıldı',
                'isFavorite' => false
            ]);
        } else {
            // Favoriye ekle
            $favorite = new Favorite();
            $favorite->setUser($user);
            $favorite->setProduct($product);
            $this->em->persist($favorite);
            $this->em->flush();
            return new JsonResponse([
                'success' => true,
                'message' => 'Ürün favorilere eklendi',
                'isFavorite' => true
            ]);
        }
    }

    #[Route('/favorite/check/{productId}', name: 'app_favorite_check', methods: ['GET'])]
    public function checkFavorite(int $productId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['isFavorite' => false]);
        }

        $product = $this->productRepository->find($productId);
        if (!$product) {
            return new JsonResponse(['isFavorite' => false]);
        }

        $favorite = $this->favoriteRepository->findOneBy([
            'user' => $user,
            'product' => $product
        ]);

        return new JsonResponse(['isFavorite' => $favorite !== null]);
    }
}
