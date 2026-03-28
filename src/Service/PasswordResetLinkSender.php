<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class PasswordResetLinkSender
{
    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly MailerInterface $mailer,
        private readonly SiteOwnerManager $siteOwnerManager,
        private readonly string $mailerFromAddress,
    ) {
    }

    /**
     * @throws ResetPasswordExceptionInterface
     */
    public function send(User $user): ResetPasswordToken
    {
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->siteOwnerManager->getOwnerName()))
            ->replyTo(new Address($this->siteOwnerManager->getOwnerEmail(), $this->siteOwnerManager->getOwnerName()))
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'user' => $user,
                'resetToken' => $resetToken,
            ]);

        $this->mailer->send($email);

        return $resetToken;
    }
}
