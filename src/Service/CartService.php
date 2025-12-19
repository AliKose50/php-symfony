<?php

namespace App\Service;

use App\Entity\Cart;
use App\Repository\CartRepository;

class CartService
{
    public function __construct(private CartRepository $cartRepository) {}

    public function getCartTotals(Cart $cart): array
    {
        $cartCount = 0;
        $cartTotal = 0.0;
        foreach ($cart->getCartItems() as $ci) {
            $cartCount += (int) $ci->getQuantity();
            $cartTotal += (float) $ci->getProduct()->getPrice() * (int) $ci->getQuantity();
        }
        return ['count' => $cartCount, 'total' => $cartTotal];
    }

    public function getCartDataForApi(Cart $cart): array
    {
        $totals = $this->getCartTotals($cart);
        $items = [];
        foreach ($cart->getCartItems() as $item) {
            $price = (float) $item->getProduct()->getPrice();
            $qty = (int) $item->getQuantity();
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
        return [
            'cartCount' => $totals['count'],
            'cartTotal' => $totals['total'],
            'items' => $items,
        ];
    }

    public function getCartCountForUser($user): int
    {
        if (!$user) {
            return 0;
        }
        $cart = $this->cartRepository->findCartWithItems($user);
        return $cart ? $this->getCartTotals($cart)['count'] : 0;
    }
}
