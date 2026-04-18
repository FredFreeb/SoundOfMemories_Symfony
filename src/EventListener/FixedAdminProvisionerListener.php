<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onRequest', priority: 2048)]
final class FixedAdminProvisionerListener
{
    private static bool $checked = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly string $fixedAdminEmail,
        private readonly string $fixedAdminPassword,
        private readonly string $fixedAdminFullName,
    ) {
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || self::$checked) {
            return;
        }

        self::$checked = true;

        if (trim($this->fixedAdminEmail) === '' || trim($this->fixedAdminPassword) === '') {
            return;
        }

        $email = mb_strtolower(trim($this->fixedAdminEmail));

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser instanceof User) {
            $this->ensureAdmin($existingUser);

            return;
        }

        $user = (new User())
            ->setEmail($email)
            ->setFullName(trim($this->fixedAdminFullName) !== '' ? trim($this->fixedAdminFullName) : 'Admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true);

        $user->setPassword($this->passwordHasher->hashPassword($user, $this->fixedAdminPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function ensureAdmin(User $user): void
    {
        $changed = false;

        if (!$user->isAdmin()) {
            $user->setRoles(array_unique(array_merge($user->getRoles(), ['ROLE_ADMIN'])));
            $changed = true;
        }

        if (!$user->isVerified()) {
            $user->setIsVerified(true);
            $changed = true;
        }

        if ($changed) {
            $this->entityManager->flush();
        }
    }
}
