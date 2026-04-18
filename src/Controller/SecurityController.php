<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

// Fred note: Ce controleur gere maintenant la connexion commune, puis renvoie chacun vers son espace.
final class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Fred note: Je choisis la destination selon le role pour garder une seule page de connexion.
        if ($this->getUser() instanceof User) {
            /** @var User $user */
            $user = $this->getUser();

            return $this->redirectToRoute($user->isAdmin() ? 'admin_dashboard' : 'app_account');
        }

        $prefilledEmail = trim((string) $request->query->get('prefill_email'));

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername() !== '' ? $authenticationUtils->getLastUsername() : $prefilledEmail,
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/deconnexion', name: 'app_logout', methods: ['GET'])]
    public function logoutConfirm(): Response
    {
        return $this->render('security/logout.html.twig');
    }

    #[Route('/deconnexion/post', name: 'app_logout_redirect', methods: ['GET'])]
    public function logoutRedirect(): Response
    {
        return $this->redirectToRoute('app_logout');
    }

    #[Route('/deconnexion/valider', name: 'app_logout_action', methods: ['POST'])]
    public function logout(): never
    {
        // Fred note: Symfony intercepte cette route lui-meme; cette methode ne doit jamais vraiment s'executer.
        throw new \LogicException('Cette methode est interceptee par le firewall Symfony.');
    }
}
