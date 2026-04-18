<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:sync-order-customers',
    description: 'Cree ou relie des comptes clients a partir des commandes existantes.',
)]
// Fred note: J'utilise cette commande pour que le back-office "Fans" reste coherent meme si certaines commandes ont ete passees sans compte explicite.
final class SyncOrderCustomersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $orders = $this->entityManager->getRepository(Order::class)->findAll();

        $createdCount = 0;
        $linkedCount = 0;

        foreach ($orders as $order) {
            if (!$order instanceof Order) {
                continue;
            }

            $email = mb_strtolower(trim($order->getCustomerEmail()));
            if ('' === $email) {
                continue;
            }

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]) ?? new User();
            $isNewUser = null === $user->getId();

            if ($isNewUser) {
                // Fred note: Je cree un compte fan minimal pour que l'admin puisse suivre la personne meme si elle n'a pas encore finalise sa vraie inscription.
                $user
                    ->setEmail($email)
                    ->setRoles([])
                    ->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(12))));
                ++$createdCount;
            }

            if (null === $user->getFullName() || '' === trim((string) $user->getFullName())) {
                $user->setFullName($order->getCustomerName() !== '' ? $order->getCustomerName() : $email);
            }

            if ((null === $user->getPhone() || '' === trim((string) $user->getPhone())) && null !== $order->getCustomerPhone()) {
                $user->setPhone($order->getCustomerPhone());
            }

            if ((null === $user->getDefaultAddress() || '' === trim((string) $user->getDefaultAddress())) && null !== $order->getShippingAddress()) {
                $user->setDefaultAddress($order->getShippingAddress());
            }

            if ((null === $user->getPostalCode() || '' === trim((string) $user->getPostalCode())) && null !== $order->getPostalCode()) {
                $user->setPostalCode($order->getPostalCode());
            }

            if ((null === $user->getCity() || '' === trim((string) $user->getCity())) && null !== $order->getCity()) {
                $user->setCity($order->getCity());
            }

            $this->entityManager->persist($user);

            if ($order->getCustomerAccount()?->getEmail() !== $user->getEmail()) {
                $order->setCustomerAccount($user);
                ++$linkedCount;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Synchronisation terminee: %d compte(s) cree(s), %d commande(s) reliee(s).',
            $createdCount,
            $linkedCount,
        ));

        return Command::SUCCESS;
    }
}
