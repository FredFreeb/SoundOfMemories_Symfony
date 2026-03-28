<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        if ($request->hasSession()) {
            $targetPath = $this->getTargetPath($request->getSession(), 'main');

            if (\is_string($targetPath) && '' !== $targetPath) {
                return new RedirectResponse($targetPath);
            }
        }

        $user = $token->getUser();

        if ($user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate($user->isAdmin() ? 'admin_dashboard' : 'app_account'));
        }

        return new RedirectResponse($this->urlGenerator->generate('store_home'));
    }
}
