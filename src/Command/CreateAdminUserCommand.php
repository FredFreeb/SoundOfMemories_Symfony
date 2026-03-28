<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:create-admin-user', description: 'Cree un utilisateur administrateur pour le back-office.')]
// Fred note: J'utilise cette commande pour creer rapidement mon premier compte admin depuis le terminal.
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED)
            ->addArgument('fullName', InputArgument::OPTIONAL, 'Nom affiche', 'Maël Bellan');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');
        $fullName = (string) $input->getArgument('fullName');

        // Fred note: Je bloque les doublons pour garder un identifiant unique par email.
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => mb_strtolower($email)]);
        if ($existingUser instanceof User) {
            $io->error('Un administrateur existe deja avec cet email.');

            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($email)
            ->setFullName($fullName)
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true);

        // Fred note: Je hash toujours le mot de passe avant de l'enregistrer en base.
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Administrateur cree: %s', $email));

        return Command::SUCCESS;
    }
}
