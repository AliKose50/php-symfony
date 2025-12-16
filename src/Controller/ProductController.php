<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\CartRepository;
use App\Repository\FavoriteRepository;
use App\Entity\Favorite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductController extends AbstractController
{
    #[Route('/urunler', name: 'app_all_products')]
    public function allProducts(ProductRepository $productRepository, CartRepository $cartRepository): Response
    {
        // Tüm ürünleri getir
        $products = $productRepository->findAll();

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = 0;
        if ($this->getUser()) {
            $cartCount = $cartRepository->findCartCountByUser($this->getUser());
        }

        return $this->render('product/all_products.html.twig', [
            'products' => $products,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/product', name: 'app_product')]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository, CartRepository $cartRepository): Response
    {
        // Kadın kategorisini bul
        $category = $categoryRepository->findOneBy(['name' => 'Kadın']);

        if ($category) {
            $products = $productRepository->findByCategoryWithImages($category->getId());
        } else {
            $products = $productRepository->findAll();
        }

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = 0;
        if ($this->getUser()) {
            $cartCount = $cartRepository->findCartCountByUser($this->getUser());
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'category' => $category,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/category/{id}', name: 'app_category_show')]
    public function showByCategory(int $id, ProductRepository $productRepository, CategoryRepository $categoryRepository, CartRepository $cartRepository): Response
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Kategori bulunamadı.');
        }

        $products = $productRepository->findByCategoryWithImages($id);

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = 0;
        if ($this->getUser()) {
            $cartCount = $cartRepository->findCartCountByUser($this->getUser());
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'category' => $category,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show')]
    public function show(int $id, ProductRepository $productRepository, CartRepository $cartRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Ürün bulunamadı.');
        }

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = 0;
        if ($this->getUser()) {
            $cartCount = $cartRepository->findCartCountByUser($this->getUser());
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/fashion', name: 'app_fashion')]
    public function fashion(ProductRepository $productRepository, CategoryRepository $categoryRepository, CartRepository $cartRepository): Response
    {
        // Kadın kategorisini bul
        $category = $categoryRepository->findOneBy(['name' => 'Kadın']);

        if ($category) {
            $products = $productRepository->findByCategoryWithImages($category->getId());
        } else {
            $products = [];
        }

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = 0;
        if ($this->getUser()) {
            $cartCount = $cartRepository->findCartCountByUser($this->getUser());
        }

        return $this->render('product/fashion.html.twig', [
            'products' => $products,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/sports', name: 'app_sports')]
    public function sports(ProductRepository $productRepository, CategoryRepository $categoryRepository, CartRepository $cartRepository): Response
    {
        // Erkek kategorisini bul (Spor olarak kullanıyoruz)
        $category = $categoryRepository->findOneBy(['name' => 'Erkek']);

        if ($category) {
            $products = $productRepository->findByCategoryWithImages($category->getId());
        } else {
            $products = [];
        }

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = 0;
        if ($this->getUser()) {
            $cartCount = $cartRepository->findCartCountByUser($this->getUser());
        }

        return $this->render('product/sports.html.twig', [

            'products' => $products,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/electronics', name: 'app_electronics')]
    public function electronics(ProductRepository $productRepository, CategoryRepository $categoryRepository, CartRepository $cartRepository): Response
    {
        // Kategoriler konfigürasyonu
        $categories = $categoryRepository->findAll();
        $products = [];

        // Tüm kategoriler için ürünleri çek
        foreach ($categories as $category) {
            $categoryProducts = $productRepository->findByCategoryWithImages($category->getId());
            $products = array_merge($products, $categoryProducts);
        }

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = 0;
        if ($this->getUser()) {
            $cartCount = $cartRepository->findCartCountByUser($this->getUser());
        }

        return $this->render('product/electronics.html.twig', [
            'products' => $products,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/home-living', name: 'app_home_living')]
    public function homeLiving(ProductRepository $productRepository, CategoryRepository $categoryRepository, CartRepository $cartRepository): Response
    {
        // Kategoriler konfigürasyonu
        $categories = $categoryRepository->findAll();
        $products = [];

        // Tüm kategoriler için ürünleri çek
        foreach ($categories as $category) {
            $categoryProducts = $productRepository->findByCategoryWithImages($category->getId());
            $products = array_merge($products, $categoryProducts);
        }

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = 0;
        if ($this->getUser()) {
            $cartCount = $cartRepository->findCartCountByUser($this->getUser());
        }

        return $this->render('product/home_living.html.twig', [
            'products' => $products,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/favorite/toggle/{productId}', name: 'app_favorite_toggle', methods: ['POST'])]
    public function toggleFavorite(
        int $productId,
        ProductRepository $productRepository,
        FavoriteRepository $favoriteRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Lütfen giriş yapın'], Response::HTTP_UNAUTHORIZED);
        }

        $product = $productRepository->find($productId);
        if (!$product) {
            return new JsonResponse(['error' => 'Ürün bulunamadı'], Response::HTTP_NOT_FOUND);
        }

        // Favoriyi kontrol et
        $favorite = $favoriteRepository->findOneBy([
            'user' => $user,
            'product' => $product
        ]);

        if ($favorite) {
            // Favoriyi kaldır
            $em->remove($favorite);
            $em->flush();
            return new JsonResponse([
                'success' => true,
                'message' => 'Ürün favorilerden kaldırıldı',
                'isFavorite' => false
            ]);
        } else {
            // Favori ekle
            $favorite = new Favorite();
            $favorite->setUser($user);
            $favorite->setProduct($product);
            $em->persist($favorite);
            $em->flush();
            return new JsonResponse([
                'success' => true,
                'message' => 'Ürün favorilere eklendi',
                'isFavorite' => true
            ]);
        }
    }

    #[Route('/favorite/check/{productId}', name: 'app_favorite_check', methods: ['GET'])]
    public function checkFavorite(
        int $productId,
        ProductRepository $productRepository,
        FavoriteRepository $favoriteRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['isFavorite' => false]);
        }

        $product = $productRepository->find($productId);
        if (!$product) {
            return new JsonResponse(['isFavorite' => false]);
        }

        $favorite = $favoriteRepository->findOneBy([
            'user' => $user,
            'product' => $product
        ]);

        return new JsonResponse(['isFavorite' => $favorite !== null]);
    }
}
