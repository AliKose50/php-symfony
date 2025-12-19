<?php

namespace App\Controller;

use App\Repository\CartRepository;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\CartService;
use App\Service\UserService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account', name: 'app_account_')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('', name: 'profile')]
    public function profile(CartRepository $cartRepository, FavoriteRepository $favoriteRepository, CartService $cartService): Response
    {
        $user = $this->getUser();

        // Kullanıcının sepetini yükle ve cartCount'ı hesapla (total quantity)
        // JOIN ile tüm verileri bir seferde çek (performans iyileştirmesi)
        $cart = null;
        $cartCount = 0;
        if ($user) {
            $cart = $cartRepository->findCartWithItems($user);
            if ($cart) {
                $cartCount = $cartService->getCartTotals($cart)['count'];
            }
        }

        // Kullanıcının favorilerini yükle
        $favorites = [];
        if ($user) {
            $favorites = $favoriteRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
            'cart' => $cart,
            'cartCount' => $cartCount,
            'favorites' => $favorites,
        ]);
    }

    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(Request $request, UserService $userService, CartRepository $cartRepository, CartService $cartService): Response
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

        if (!$userService->updateUser($user, $fullName, $email, $newPassword)) {
            $this->addFlash('error', 'Ad soyad ve e-posta boş bırakılamaz veya e-posta zaten kullanılıyor.');
            return $this->redirectToRoute('app_account_profile');
        }

        $this->addFlash('success', 'Hesap bilgileriniz güncellendi.');

        // Yeniden yüklemek için sepet bilgisini de gönder ve cartCount'ı hesapla (total quantity)
        $cart = $cartRepository->findCartWithItems($user);
        $cartCount = 0;
        if ($cart) {
            $cartCount = $cartService->getCartTotals($cart)['count'];
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
            'cart' => $cart,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/favorite/remove/{favoriteId}', name: 'app_favorite_remove', methods: ['POST'])]
    public function removeFavorite(
        int $favoriteId,
        FavoriteRepository $favoriteRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $favorite = $favoriteRepository->find($favoriteId);
        if (!$favorite || $favorite->getUser() !== $user) {
            return new JsonResponse(['error' => 'Favorite not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($favorite);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Ürün favorilerden kaldırıldı']);
    }
}
