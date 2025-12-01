<?php

namespace App\Controller;

use App\Repository\CartRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account', name: 'app_account_')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('', name: 'profile')]
    public function profile(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        $cart = null;
        $cartCount = 0;
        if ($user) {
            $cart = $cartRepository->findOneBy(['full_name' => $user]);
            if ($cart) {
                foreach ($cart->getCartItems() as $item) {
                    $cartCount += (int) $item->getQuantity();
                }
            }
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
            'cart' => $cart,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $em, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // CSRF token validation
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('account_update', $token)) {
            $this->addFlash('error', 'Geçersiz istekte bulundunuz.');
            return $this->redirectToRoute('app_account_profile');
        }

        $fullName = trim((string) $request->request->get('fullName', ''));
        $email = trim((string) $request->request->get('email', ''));
        $newPassword = trim((string) $request->request->get('password', ''));

        // Basit doğrulamalar
        if ($fullName === '' || $email === '') {
            $this->addFlash('error', 'Ad soyad ve e-posta boş bırakılamaz.');
            return $this->redirectToRoute('app_account_profile');
        }

        // Email benzersizliği kontrolü
        $existing = $userRepository->findOneBy(['email' => $email]);
        if ($existing && $existing->getId() !== $user->getId()) {
            $this->addFlash('error', 'Bu e-posta zaten kullanılıyor.');
            return $this->redirectToRoute('app_account_profile');
        }

        $user->setFullName($fullName);
        $user->setEmail($email);

        if ($newPassword !== '') {
            $hashed = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashed);
        }

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Hesap bilgileriniz güncellendi.');

        // Yeniden yüklemek için sepet bilgisini de gönder ve cartCount'ı hesapla (total quantity)
        $cart = $cartRepository->findOneBy(['full_name' => $user]);
        $cartCount = 0;
        if ($cart) {
            foreach ($cart->getCartItems() as $item) {
                $cartCount += (int) $item->getQuantity();
            }
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
            'cart' => $cart,
            'cartCount' => $cartCount,
        ]);
    }
}
