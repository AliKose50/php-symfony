<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\CartService;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart', name: 'app_cart_')]
#[IsGranted('ROLE_USER')]
final class CartController extends AbstractController
{
    #[Route('/add/{productId}', name: 'add', methods: ['POST'])]
    public function addToCart(
        int $productId,
        Request $request,
        ProductRepository $productRepository,
        CartRepository $cartRepository,
        CartService $cartService,
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

        // Kullanıcının sepetini bul veya oluştur (Cart entity'sindeki alan adı `full_name` olarak tanımlı)
        $cart = $cartRepository->findOneBy(['full_name' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setFullName($user);
            $em->persist($cart);
        }

        // Ürünü sepete ekle
        $quantity = (int) $request->request->get('quantity', 1);

        // Aynı ürün varsa quantity'sini arttır
        $existingItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $productId) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $existingItem->setQuantity($existingItem->getQuantity() + $quantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $em->persist($cartItem);
        }

        $em->flush();

        // Get cart totals using service
        $totals = $cartService->getCartTotals($cart);

        return new JsonResponse([
            'success' => true,
            'message' => 'Ürün sepete eklendi',
            'cartCount' => $totals['count'],
            'cartTotal' => $totals['total'],
        ]);
    }

    #[Route('/view', name: 'view')]
    public function view(CartRepository $cartRepository, CartService $cartService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartRepository->findOneBy(['full_name' => $user]);
        $cartData = $cartService->getCartDataForView($cart);

        return $this->render('cart/index.html.twig', [
            'cart' => $cartData
        ]);
    }

    #[Route('/update/{itemId}', name: 'update', methods: ['POST'])]
    public function updateCartItem(
        int $itemId,
        Request $request,
        CartService $cartService,
        EntityManagerInterface $em,
        \App\Repository\CartItemRepository $itemRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $cartItem = $itemRepository->find($itemId);
        if (!$cartItem || $cartItem->getCart()->getFullName() !== $user) {
            return new JsonResponse(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $change = (int) $request->request->get('quantity_change', 0);
        $newQuantity = $cartItem->getQuantity() + $change;

        if ($newQuantity <= 0) {
            $em->remove($cartItem);
        } else {
            $cartItem->setQuantity($newQuantity);
        }

        $em->flush();

        // Get cart totals using service
        $cart = $cartItem->getCart();
        $totals = $cartService->getCartTotals($cart);

        $lineTotal = 0.0;
        if ($newQuantity > 0) {
            $lineTotal = (float) $cartItem->getProduct()->getPrice() * $newQuantity;
        }

        return new JsonResponse([
            'success' => true,
            'quantity' => max(0, $newQuantity),
            'cartCount' => $totals['count'],
            'cartTotal' => $totals['total'],
            'lineTotal' => $lineTotal,
        ]);
    }

    #[Route('/remove/{itemId}', name: 'remove', methods: ['POST'])]
    public function removeCartItem(
        int $itemId,
        CartService $cartService,
        EntityManagerInterface $em,
        \App\Repository\CartItemRepository $itemRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $cartItem = $itemRepository->find($itemId);
        if (!$cartItem || $cartItem->getCart()->getFullName() !== $user) {
            return new JsonResponse(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $cart = $cartItem->getCart();
        $em->remove($cartItem);
        $em->flush();

        // Get cart totals using service
        $totals = $cartService->getCartTotals($cart);

        return new JsonResponse([
            'success' => true,
            'cartCount' => $totals['count'],
            'cartTotal' => $totals['total'],
        ]);
    }

    #[Route('/api/view', name: 'api_view', methods: ['GET'])]
    public function apiView(CartRepository $cartRepository, CartService $cartService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // JOIN ile tüm verileri bir seferde çek (N+1 query problemi çözümü)
        $cart = $cartRepository->findCartWithItems($user);

        if (!$cart) {
            return new JsonResponse([
                'cartCount' => 0,
                'cartTotal' => 0.0,
                'items' => [],
            ]);
        }

        return new JsonResponse($cartService->getCartDataForApi($cart));
    }
}
