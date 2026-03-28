<?php

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final class EmailVerifier
{
    public function __construct(
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, TemplatedEmail $email): void
    {
        $userId = method_exists($user, 'getId') ? (string) $user->getId() : $user->getUserIdentifier();
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $userId,
            method_exists($user, 'getEmail') ? (string) $user->getEmail() : $user->getUserIdentifier(),
            ['id' => method_exists($user, 'getId') ? $user->getId() : null]
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        $userId = method_exists($user, 'getId') ? (string) $user->getId() : $user->getUserIdentifier();
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            $userId,
            method_exists($user, 'getEmail') ? (string) $user->getEmail() : $user->getUserIdentifier(),
        );

        if (method_exists($user, 'setIsVerified')) {
            $user->setIsVerified(true);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
