<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();

        // Eğer önceki istek bir korunan sayfaya yönlendirdiyse, oraya geri gönder
        $session = $request->getSession();
        if ($session) {
            $targetPath = $session->get('_security.main.target_path');
            if ($targetPath) {
                return new RedirectResponse($targetPath);
            }
        }

        // Eğer kullanıcı admin rolüne sahipse admin paneline yönlendir
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('admin'));
        }

        // Normal kullanıcı ise ana sayfaya yönlendir
        return new RedirectResponse($this->router->generate('app_home'));
    }
}
