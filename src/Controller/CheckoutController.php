<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Service\IyzicopayService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkout')]
#[IsGranted('ROLE_USER')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'app_checkout')]
    public function index(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['full_name' => $user]);

        if (!$cart || $cart->getCartItems()->count() === 0) {
            return $this->redirectToRoute('app_home');
        }

        // Sepet toplamını hesapla
        $total = 0;
        foreach ($cart->getCartItems() as $item) {
            $total += (float) $item->getProduct()->getPrice() * $item->getQuantity();
        }

        // Sepet sayısını hesapla
        $cartCount = 0;
        foreach ($cart->getCartItems() as $item) {
            $cartCount += (int) $item->getQuantity();
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'cartTotal' => $total,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/payment', name: 'app_checkout_payment', methods: ['POST'])]
    public function payment(
        Request $request,
        CartRepository $cartRepository,
        OrderRepository $orderRepository,
        EntityManagerInterface $em,
        IyzicopayService $iyzicopay
    ): Response {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['full_name' => $user]);

        if (!$cart || $cart->getCartItems()->count() === 0) {
            return $this->json(['error' => 'Sepet boş'], 400);
        }

        // Siparişi oluştur
        $order = new Order();
        $order->setUser($user);
        $order->setStatus('pending');

        // Form verilerini al
        $fullName = $request->request->get('fullName');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        $address = $request->request->get('address');
        $city = $request->request->get('city');
        $district = $request->request->get('district');
        $zipCode = $request->request->get('zipCode');

        $order->setFullName($fullName);
        $order->setEmail($email);
        $order->setPhone($phone);
        $order->setAddress($address);
        $order->setCity($city);
        $order->setDistrict($district);
        $order->setZipCode($zipCode);

        // Sipariş öğelerini ekle
        $total = 0;
        $basketItems = [];

        foreach ($cart->getCartItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $price = (float) $product->getPrice();
            $quantity = $cartItem->getQuantity();
            $lineTotal = $price * $quantity;

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setPrice($price);
            $order->addItem($orderItem);

            $total += $lineTotal;

            $basketItems[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'category' => $product->getCategory()->getName(),
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        $order->setTotalAmount($total);
        $em->persist($order);
        $em->flush();

        // Iyzico ödeme isteği
        $paymentData = [
            'cardHolderName' => $request->request->get('cardHolderName'),
            'cardNumber' => str_replace(' ', '', $request->request->get('cardNumber')),
            'expireMonth' => $request->request->get('expireMonth'),
            'expireYear' => $request->request->get('expireYear'),
            'cvc' => $request->request->get('cvc'),

            'buyerName' => explode(' ', $fullName)[0],
            'buyerSurname' => explode(' ', $fullName, 2)[1] ?? '',
            'gsmNumber' => $phone,
            'email' => $email,
            'identityNumber' => '12345678901', // Gerçek ID gerekli - formdaki veriden alınmalı
            'lastLoginDate' => date('Y-m-d H:i:s'),
            'registrationDate' => date('Y-m-d H:i:s'),
            'registrationAddress' => $address,
            'ip' => $request->getClientIp(),
            'city' => $city,
            'country' => 'Türkiye',
            'zipCode' => $zipCode,
            'contactName' => $fullName,
            'address' => $address,
            'price' => (string) $total,
            'paidPrice' => (string) $total,
            'installment' => (int) $request->request->get('installment', 1),
            'conversationId' => (string) $order->getId(),
            'basketId' => (string) $order->getId(),
            'callbackUrl' => $this->generateUrl('app_checkout_callback', [], true),
            'basketItems' => $basketItems,
        ];

        try {
            $paymentRequest = $iyzicopay->createPaymentRequest($paymentData);
            $response = $iyzicopay->processPayment($paymentRequest);

            if ($response->getStatus() === 'success') {
                $order->setStatus('completed');
                $order->setPaymentId($response->getPaymentId());
                $order->setPaidAt(new \DateTimeImmutable());

                // Sepeti temizle
                foreach ($cart->getCartItems() as $item) {
                    $cart->removeCartItem($item);
                    $em->remove($item);
                }
                $em->flush();

                return $this->json([
                    'success' => true,
                    'orderId' => $order->getId(),
                    'message' => 'Ödeme başarılı'
                ]);
            } else {
                $order->setStatus('failed');
                $em->flush();

                return $this->json([
                    'success' => false,
                    'message' => 'Ödeme başarısız: ' . $response->getErrorMessage()
                ], 400);
            }
        } catch (\Exception $e) {
            $order->setStatus('failed');
            $em->flush();

            return $this->json([
                'success' => false,
                'message' => 'Ödeme sırasında hata: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/callback', name: 'app_checkout_callback')]
    public function callback(Request $request, OrderRepository $orderRepository, EntityManagerInterface $em): Response
    {
        $orderId = $request->query->get('orderId');
        $status = $request->query->get('status');

        if ($orderId && $status) {
            $order = $orderRepository->find($orderId);
            if ($order) {
                $order->setStatus($status === 'success' ? 'completed' : 'failed');
                $em->flush();
            }
        }

        return $this->render('checkout/callback.html.twig');
    }

    #[Route('/success/{id}', name: 'app_checkout_success')]
    public function success(Order $order, CartRepository $cartRepository): Response
    {
        $user = $this->getUser();

        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $cart = $cartRepository->findOneBy(['full_name' => $user]);

        // Sepet sayısını hesapla
        $cartCount = 0;
        if ($cart) {
            foreach ($cart->getCartItems() as $item) {
                $cartCount += (int) $item->getQuantity();
            }
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
            'cartCount' => $cartCount,
            'cart' => $cart,
        ]);
    }
}
