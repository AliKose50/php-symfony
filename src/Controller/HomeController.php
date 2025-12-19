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
use App\Service\CartService;
use App\Service\ContactService;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository, CartService $cartService): Response
    {
        $products = $productRepository->findAll();

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cartCount = $cartService->getCartCountForUser($this->getUser());

        return $this->render('home/index.html.twig', [
            'products' => $products,
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
    public function contact(Request $request, ContactService $contactService): Response
    {
        $success = false;
        $error = null;

        // Geçici test - production'da kaldırılacak
        if ($request->query->get('demo') === 'success') {
            $success = true;
        }

        if ($request->isMethod('POST')) {
            try {
                $contactService->sendContactEmail($request->request->all());
                $success = true;
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            } catch (\RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('pages/contact.html.twig', [
            'success' => $success,
            'error' => $error,
        ]);
    }
}
