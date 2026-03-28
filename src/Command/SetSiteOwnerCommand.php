<?php

namespace App\Command;

use App\Entity\User;
use App\Service\SiteOwnerManager;
use App\Service\SiteOwnerTransferNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:site-owner:set', description: 'Definit le proprietaire du site depuis un canal serveur prive.')]
final class SetSiteOwnerCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SiteOwnerManager $siteOwnerManager,
        private readonly SiteOwnerTransferNotifier $siteOwnerTransferNotifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email du nouveau propriétaire')
            ->addArgument('fullName', InputArgument::OPTIONAL, 'Nom affiché du nouveau propriétaire');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        $fullName = trim((string) $input->getArgument('fullName'));

        if ('' === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Merci de fournir une adresse email valide.');

            return Command::INVALID;
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $currentOwnerEmail = $this->siteOwnerManager->getOwnerEmail();
        $currentOwner = $currentOwnerEmail !== '' ? $userRepository->findOneBy(['email' => $currentOwnerEmail]) : null;
        $newOwner = $userRepository->findOneBy(['email' => $email]);

        if (!$newOwner instanceof User) {
            $newOwner = (new User())
                ->setEmail($email)
                ->setFullName($fullName !== '' ? $fullName : $this->guessNameFromEmail($email))
                ->setRoles(['ROLE_ADMIN'])
                ->setIsVerified(true);

            $newOwner->setPassword($this->passwordHasher->hashPassword($newOwner, bin2hex(random_bytes(24))));
            $this->entityManager->persist($newOwner);
        } else {
            if ($fullName !== '') {
                $newOwner->setFullName($fullName);
            }

            $newOwner->setRoles(['ROLE_ADMIN']);
            $newOwner->setIsVerified(true);
        }

        if ($currentOwner instanceof User && $currentOwner->getEmail() !== $newOwner->getEmail()) {
            $remainingRoles = array_values(array_filter(
                $currentOwner->getRoles(),
                static fn (string $role): bool => 'ROLE_ADMIN' !== $role && 'ROLE_USER' !== $role
            ));
            $currentOwner->setRoles($remainingRoles);
        }

        $this->siteOwnerManager->saveOwner(
            $newOwner->getFullName() ?: ($fullName !== '' ? $fullName : $this->guessNameFromEmail($email)),
            $newOwner->getEmail() ?? $email
        );

        $this->entityManager->flush();

        if ($this->siteOwnerTransferNotifier->isDeliveryEnabled()) {
            $this->siteOwnerTransferNotifier->send($newOwner);
            $io->success(sprintf('Le propriétaire du site est maintenant %s. Un email de notification a été envoyé.', $email));
        } else {
            $io->success(sprintf('Le propriétaire du site est maintenant %s.', $email));
            $io->warning('La messagerie n’est pas active sur cet environnement. Aucun email réel n’a pu être envoyé.');
        }

        return Command::SUCCESS;
    }

    private function guessNameFromEmail(string $email): string
    {
        $localPart = strstr($email, '@', true) ?: $email;

        return ucwords(str_replace(['.', '-', '_'], ' ', $localPart));
    }
}
