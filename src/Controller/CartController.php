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

        // Recalculate totals: cartCount = total quantity of all items
        $cartCount = 0;
        $cartTotal = 0.0;
        foreach ($cart->getCartItems() as $ci) {
            $cartCount += (int) $ci->getQuantity();
            $cartTotal += (float) $ci->getProduct()->getPrice() * (int) $ci->getQuantity();
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Ürün sepete eklendi',
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
        ]);
    }

    #[Route('/view', name: 'view')]
    public function view(): Response
    {
        // JavaScript ile veri çekildiği için sadece template render ediliyor
        return $this->render('cart/index.html.twig');
    }

    #[Route('/update/{itemId}', name: 'update', methods: ['POST'])]
    public function updateCartItem(
        int $itemId,
        Request $request,
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

        $delta = (int) $request->request->get('delta', 0);
        $newQuantity = $cartItem->getQuantity() + $delta;

        if ($newQuantity <= 0) {
            $em->remove($cartItem);
        } else {
            $cartItem->setQuantity($newQuantity);
        }

        $em->flush();

        // Recalculate cart totals: cartCount = total quantity of all items
        $cart = $cartItem->getCart();
        $cartCount = 0;
        $cartTotal = 0.0;
        foreach ($cart->getCartItems() as $ci) {
            $cartCount += (int) $ci->getQuantity();
            $cartTotal += (float) $ci->getProduct()->getPrice() * (int) $ci->getQuantity();
        }

        $lineTotal = 0.0;
        if ($newQuantity > 0) {
            $lineTotal = (float) $cartItem->getProduct()->getPrice() * $newQuantity;
        }

        return new JsonResponse([
            'success' => true,
            'quantity' => max(0, $newQuantity),
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
            'lineTotal' => $lineTotal,
        ]);
    }

    #[Route('/remove/{itemId}', name: 'remove', methods: ['POST'])]
    public function removeCartItem(
        int $itemId,
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

        // Recalculate cart totals: cartCount = total quantity of all items
        $cartCount = 0;
        $cartTotal = 0.0;
        if ($cart) {
            foreach ($cart->getCartItems() as $ci) {
                $cartCount += (int) $ci->getQuantity();
                $cartTotal += (float) $ci->getProduct()->getPrice() * (int) $ci->getQuantity();
            }
        }

        return new JsonResponse([
            'success' => true,
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
        ]);
    }

    #[Route('/api/view', name: 'api_view', methods: ['GET'])]
    public function apiView(CartRepository $cartRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // JOIN ile tüm verileri bir seferde çek (N+1 query problemi çözümü)
        $cart = $cartRepository->findCartWithItems($user);

        $cartCount = 0;
        $total = 0.0;
        $items = [];
        if ($cart) {
            foreach ($cart->getCartItems() as $item) {
                $cartCount += (int) $item->getQuantity();
                $price = (float) $item->getProduct()->getPrice();
                $qty = (int) $item->getQuantity();
                $total += $price * $qty;

                $img = $item->getProduct()->getProductImages()->first();
                $imageUrl = $img ? '/uploads/product_images/' . $img->getImageUrl() : null;

                $items[] = [
                    'id' => $item->getId(),
                    'product' => [
                        'id' => $item->getProduct()->getId(),
                        'name' => $item->getProduct()->getName(),
                        'price' => $price,
                        'category' => $item->getProduct()->getCategory()->getName(),
                        'image' => $imageUrl,
                    ],
                    'quantity' => $qty,
                    'lineTotal' => $price * $qty,
                ];
            }
        }

        return new JsonResponse([
            'cartCount' => $cartCount,
            'cartTotal' => $total,
            'items' => $items,
        ]);
    }
}
