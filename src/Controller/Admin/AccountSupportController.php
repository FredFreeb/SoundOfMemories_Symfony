<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\PasswordResetLinkSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;

#[Route('/la-porte-secrete/comptes')]
final class AccountSupportController extends AbstractController
{
    #[Route('/{id}/envoyer-reinitialisation', name: 'admin_account_send_reset_link', methods: ['GET'])]
    public function sendResetLink(
        User $user,
        PasswordResetLinkSender $passwordResetLinkSender,
        TranslatorInterface $translator,
        Request $request,
        string $mailerDsn,
    ): RedirectResponse {
        if ($user->isAdmin()) {
            $this->addFlash('warning', 'La réinitialisation depuis le back-office est réservée aux comptes clients.');

            $referer = $request->headers->get('referer');

            if (\is_string($referer) && '' !== $referer) {
                return new RedirectResponse($referer);
            }

            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $passwordResetLinkSender->send($user);

            if (str_starts_with($mailerDsn, 'null://')) {
                $this->addFlash('warning', "Le lien de réinitialisation a été préparé pour {$user->getEmail()}, mais la messagerie n’est pas active sur cet environnement.");
            } elseif (str_contains($mailerDsn, 'dev-sendmail.php')) {
                $this->addFlash('success', "Le lien de réinitialisation est disponible dans la boîte mail locale pour {$user->getEmail()}.");
            } else {
                $this->addFlash('success', "Le lien de réinitialisation a été envoyé à {$user->getEmail()}.");
            }
        } catch (ResetPasswordExceptionInterface $exception) {
            $this->addFlash('error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
                $translator->trans($exception->getReason(), [], 'ResetPasswordBundle')
            ));
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible d’envoyer le lien de réinitialisation pour le moment.');
        }

        $referer = $request->headers->get('referer');

        if (\is_string($referer) && '' !== $referer) {
            return new RedirectResponse($referer);
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}
