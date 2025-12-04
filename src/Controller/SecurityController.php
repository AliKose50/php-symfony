<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, CartRepository $cartRepository): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Kullanıcının sepetini yükle
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

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'cart' => $cart,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route(path: '/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository, CartRepository $cartRepository): Response
    {
        // Kullanıcı zaten giriş yapmışsa, yönlendir
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = null;
        $isJsonRequest = $request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json');

        if ($request->isMethod('POST')) {
            $fullName = trim((string) $request->request->get('fullName', ''));
            $email = trim((string) $request->request->get('email', ''));
            $password = trim((string) $request->request->get('password', ''));
            $confirmPassword = trim((string) $request->request->get('confirmPassword', ''));

            // Basit doğrulamalar
            if ($fullName === '' || $email === '' || $password === '') {
                $error = 'Tüm alanlar zorunludur.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Şifreler eşleşmiyor.';
            } elseif (strlen($password) < 6) {
                $error = 'Şifre en az 6 karakter olmalıdır.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Geçerli bir e-posta adresi girin.';
            } else {
                // Email benzersizliği kontrolü
                $existing = $userRepository->findOneBy(['email' => $email]);
                if ($existing) {
                    $error = 'Bu e-posta adresi zaten kullanılıyor.';
                } else {
                    // Yeni kullanıcı oluştur
                    $user = new User();
                    $user->setEmail($email);
                    $user->setFullName($fullName);
                    $user->setRoles(['ROLE_USER']);
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);

                    $em->persist($user);
                    $em->flush();

                    if ($isJsonRequest) {
                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Kayıt başarıyla tamamlandı! Giriş yapınız.'
                        ]);
                    }

                    $this->addFlash('success', 'Kayıt başarıyla tamamlandı! Şimdi giriş yapabilirsiniz.');
                    return $this->redirectToRoute('app_login');
                }
            }

            // Hata varsa JSON dön
            if ($error && $isJsonRequest) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $error
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Kullanıcının sepetini yükle
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

        return $this->render('security/register.html.twig', [
            'error' => $error,
            'cart' => $cart,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
