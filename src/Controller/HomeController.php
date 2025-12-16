<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository, CartRepository $cartRepository): Response
    {
        $products = $productRepository->findAll();

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cart = null;
        $cartCount = 0;
        if ($this->getUser()) {
            $cart = $cartRepository->findOneBy(['full_name' => $this->getUser()]);
            if ($cart) {
                foreach ($cart->getCartItems() as $item) {
                    $cartCount += (int) $item->getQuantity();
                }
            }
        }

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'cart' => $cart,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/hakkimizda', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }

    #[Route('/sss', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('pages/faq.html.twig');
    }

    #[Route('/kargo-ve-iade', name: 'app_shipping')]
    public function shipping(): Response
    {
        return $this->render('pages/shipping.html.twig');
    }

    #[Route('/gizlilik-politikasi', name: 'app_privacy')]
    public function privacy(): Response
    {
        return $this->render('pages/privacy.html.twig');
    }

    #[Route('/iletisim', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $success = false;
        $error = null;

        // Geçici test - production'da kaldırılacak
        if ($request->query->get('demo') === 'success') {
            $success = true;
        }

        if ($request->isMethod('POST')) {
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');
            $privacy = $request->request->get('privacy');

            // Validasyon
            if (empty($firstName) || empty($lastName) || empty($email) || empty($subject) || empty($message)) {
                $error = 'Lütfen tüm zorunlu alanları doldurun.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Geçerli bir e-posta adresi girin.';
            } elseif (!$privacy) {
                $error = 'Gizlilik politikasını kabul etmeniz gerekir.';
            } else {
                try {
                    // E-posta gönderme
                    $emailMessage = (new Email())
                        ->from($email)
                        ->to('ali.kose@guzelteknoloji.com')
                        ->subject('Lumina İletişim Formu: ' . $subject)
                        ->html($this->renderView('emails/contact.html.twig', [
                            'firstName' => $firstName,
                            'lastName' => $lastName,
                            'email' => $email,
                            'phone' => $phone,
                            'subject' => $subject,
                            'message' => $message,
                        ]));

                    $mailer->send($emailMessage);
                    $success = true;
                } catch (\Exception $e) {
                    error_log('Email sending failed: ' . $e->getMessage());
                    $error = 'E-posta gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
                }
            }
        }

        return $this->render('pages/contact.html.twig', [
            'success' => $success,
            'error' => $error,
        ]);
    }
}
