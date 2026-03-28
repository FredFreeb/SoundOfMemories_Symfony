<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SiteOwnerTransferNotifier
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SiteOwnerManager $siteOwnerManager,
        private readonly string $mailerFromAddress,
        private readonly string $mailerDsn,
    ) {
    }

    public function isDeliveryEnabled(): bool
    {
        return !str_starts_with($this->mailerDsn, 'null://');
    }

    public function send(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->siteOwnerManager->getOwnerName()))
            ->replyTo(new Address($this->siteOwnerManager->getOwnerEmail(), $this->siteOwnerManager->getOwnerName()))
            ->to((string) $user->getEmail())
            ->subject('Vous êtes désormais le nouveau propriétaire du site')
            ->htmlTemplate('emails/site_owner_transfer.html.twig')
            ->context([
                'user' => $user,
                'siteOwner' => $this->siteOwnerManager,
                'loginUrl' => $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'forgotPasswordUrl' => $this->urlGenerator->generate('app_forgot_password_request', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        $this->mailer->send($email);
    }
}
