<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class SocialAuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Security $security,
        private readonly bool $socialAuthGoogleEnabled,
        private readonly bool $socialAuthAppleEnabled,
        private readonly string $oauthGoogleClientId,
        private readonly string $oauthGoogleClientSecret,
        private readonly string $oauthAppleClientId,
        private readonly string $oauthAppleTeamId,
        private readonly string $oauthAppleKeyFileId,
        private readonly string $oauthAppleKeyFilePath,
    ) {
    }

    #[Route('/connexion/google', name: 'app_connect_google', methods: ['GET'])]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        if (!$this->socialAuthGoogleEnabled || '' === trim($this->oauthGoogleClientId) || '' === trim($this->oauthGoogleClientSecret)) {
            $this->addFlash('warning', 'La connexion Google n’est pas encore configurée sur cette instance.');

            return $this->redirectToRoute('app_login');
        }

        try {
            return $clientRegistry
                ->getClient('google_main')
                ->redirect(['email', 'profile']);
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Impossible de démarrer la connexion Google pour le moment.');

            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/connexion/google/check', name: 'app_connect_google_check', methods: ['GET'])]
    public function connectGoogleCheck(ClientRegistry $clientRegistry): Response
    {
        try {
            $oauthUser = $clientRegistry->getClient('google_main')->fetchUser();
        } catch (IdentityProviderException|\Throwable $exception) {
            $this->addFlash('error', 'La connexion Google a échoué. Réessaie dans un instant.');

            return $this->redirectToRoute('app_login');
        }

        if (\method_exists($oauthUser, 'getEmailVerified') && true !== $oauthUser->getEmailVerified()) {
            $this->addFlash('error', 'Google n’a pas confirmé cette adresse email. Utilise plutôt le compte local.');

            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->getUser();

        if ($currentUser instanceof User) {
            try {
                $this->linkProviderToCurrentUser(
                    $currentUser,
                    'google',
                    (string) $oauthUser->getId(),
                    $this->normalizeNullableString($oauthUser->getEmail()),
                    $this->normalizeNullableString($oauthUser->getFirstName()),
                    $this->normalizeNullableString($oauthUser->getLastName()),
                    $this->extractGoogleAvatarUrl($oauthUser),
                );
            } catch (\RuntimeException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->redirectToRoute('app_account');
            }

            $this->addFlash('success', 'Google est maintenant lié à ce compte.');

            return $this->redirectToRoute('app_account');
        }

        try {
            $user = $this->resolveSocialUser(
                provider: 'google',
                providerId: (string) $oauthUser->getId(),
                email: $this->normalizeNullableString($oauthUser->getEmail()),
                firstName: $this->normalizeNullableString($oauthUser->getFirstName()),
                lastName: $this->normalizeNullableString($oauthUser->getLastName()),
                avatarUrl: $this->extractGoogleAvatarUrl($oauthUser),
            );
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_login');
        }

        return $this->completeLogin($user);
    }

    #[Route('/connexion/apple', name: 'app_connect_apple', methods: ['GET'])]
    public function connectApple(ClientRegistry $clientRegistry): Response
    {
        $isAppleConfigured = $this->socialAuthAppleEnabled
            && '' !== trim($this->oauthAppleClientId)
            && '' !== trim($this->oauthAppleTeamId)
            && '' !== trim($this->oauthAppleKeyFileId)
            && '' !== trim($this->oauthAppleKeyFilePath)
            && is_file($this->oauthAppleKeyFilePath);

        if (!$isAppleConfigured) {
            $this->addFlash('warning', 'La connexion Apple n’est pas encore configurée sur cette instance.');

            return $this->redirectToRoute('app_login');
        }

        try {
            return $clientRegistry
                ->getClient('apple_main')
                ->redirect(['name', 'email']);
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Impossible de démarrer la connexion Apple pour le moment.');

            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/connexion/apple/check', name: 'app_connect_apple_check', methods: ['GET', 'POST'])]
    public function connectAppleCheck(ClientRegistry $clientRegistry): Response
    {
        try {
            $oauthUser = $clientRegistry->getClient('apple_main')->fetchUser();
        } catch (IdentityProviderException|\Throwable $exception) {
            $this->addFlash('error', 'La connexion Apple a échoué. Réessaie dans un instant.');

            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->getUser();

        if ($currentUser instanceof User) {
            try {
                $this->linkProviderToCurrentUser(
                    $currentUser,
                    'apple',
                    (string) $oauthUser->getId(),
                    $this->normalizeNullableString($oauthUser->getEmail()),
                    $this->normalizeNullableString($oauthUser->getFirstName()),
                    $this->normalizeNullableString($oauthUser->getLastName()),
                );
            } catch (\RuntimeException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->redirectToRoute('app_account');
            }

            $this->addFlash('success', 'Apple est maintenant lié à ce compte.');

            return $this->redirectToRoute('app_account');
        }

        try {
            $user = $this->resolveSocialUser(
                provider: 'apple',
                providerId: (string) $oauthUser->getId(),
                email: $this->normalizeNullableString($oauthUser->getEmail()),
                firstName: $this->normalizeNullableString($oauthUser->getFirstName()),
                lastName: $this->normalizeNullableString($oauthUser->getLastName()),
            );
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_login');
        }

        return $this->completeLogin($user);
    }

    private function resolveSocialUser(string $provider, string $providerId, ?string $email, ?string $firstName, ?string $lastName, ?string $avatarUrl = null): User
    {
        $providerId = trim($providerId);

        if ('' === $providerId) {
            throw new \RuntimeException('Le fournisseur de connexion n’a pas renvoyé d’identifiant exploitable.');
        }

        $providerField = 'google' === $provider ? 'googleId' : 'appleId';
        $email = $this->normalizeEmail($email);
        $userByProvider = $this->userRepository->findOneBy([$providerField => $providerId]);
        $userByEmail = null !== $email ? $this->userRepository->findOneBy(['email' => $email]) : null;

        if ($userByProvider instanceof User && $userByEmail instanceof User && $userByProvider->getId() !== $userByEmail->getId()) {
            throw new \RuntimeException('Cette adresse email est déjà rattachée à un autre compte.');
        }

        $user = $userByProvider ?? $userByEmail;

        if (!$user instanceof User) {
            if (null === $email) {
                throw new \RuntimeException('Cette connexion sociale n’a pas fourni d’adresse email utilisable pour créer le compte.');
            }

            $user = new User();
            $user
                ->setRoles([])
                ->setEmail($email)
                ->setFullName($this->buildFullName($firstName, $lastName, $email))
                ->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))))
                ->setIsVerified(true);

            $this->entityManager->persist($user);
        } else {
            if (null !== $email) {
                $conflictingEmailUser = $this->userRepository->findOneBy(['email' => $email]);

                if (!$conflictingEmailUser instanceof User || $conflictingEmailUser->getId() === $user->getId()) {
                    $user->setEmail($email);
                }
            }

            if (null === $user->getPassword() || '' === $user->getPassword()) {
                $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
            }

            if (null !== $email || !$user->isVerified()) {
                $user->setIsVerified(true);
            }

            $currentName = trim((string) $user->getFullName());
            $socialName = $this->buildFullName($firstName, $lastName, $email);

            if ('' === $currentName || 'Client Expédition Mystère' === $currentName) {
                $user->setFullName($socialName);
            }
        }

        if ('google' === $provider) {
            $user
                ->setGoogleId($providerId)
                ->setGoogleAvatarUrl($this->normalizeAvatarUrl($avatarUrl));
        } else {
            $user->setAppleId($providerId);
        }

        $this->entityManager->flush();

        return $user;
    }

    private function completeLogin(User $user): Response
    {
        $response = $this->security->login($user, 'form_login', 'main');

        return $response ?? $this->redirectToRoute($user->isAdmin() ? 'admin_dashboard' : 'app_account');
    }

    private function linkProviderToCurrentUser(User $currentUser, string $provider, string $providerId, ?string $email, ?string $firstName, ?string $lastName, ?string $avatarUrl = null): void
    {
        $providerId = trim($providerId);

        if ('' === $providerId) {
            throw new \RuntimeException('Le fournisseur de connexion n’a pas renvoyé d’identifiant exploitable.');
        }

        $providerField = 'google' === $provider ? 'googleId' : 'appleId';
        $email = $this->normalizeEmail($email);
        $userByProvider = $this->userRepository->findOneBy([$providerField => $providerId]);

        if ($userByProvider instanceof User && $userByProvider->getId() !== $currentUser->getId()) {
            throw new \RuntimeException('Cette connexion est déjà liée à un autre compte.');
        }

        if (null !== $email) {
            $userByEmail = $this->userRepository->findOneBy(['email' => $email]);

            if ($userByEmail instanceof User && $userByEmail->getId() !== $currentUser->getId()) {
                throw new \RuntimeException('Cette adresse email est déjà utilisée par un autre compte.');
            }
        }

        if ('google' === $provider) {
            $currentUser->setGoogleId($providerId);

            if (null !== $this->normalizeAvatarUrl($avatarUrl)) {
                $currentUser->setGoogleAvatarUrl($this->normalizeAvatarUrl($avatarUrl));
            }
        } else {
            $currentUser->setAppleId($providerId);
        }

        if (null !== $email && (null === $currentUser->getEmail() || '' === $currentUser->getEmail())) {
            $currentUser->setEmail($email);
        }

        if (!$currentUser->isVerified()) {
            $currentUser->setIsVerified(true);
        }

        $currentName = trim((string) $currentUser->getFullName());

        if ('' === $currentName || 'Client Expédition Mystère' === $currentName) {
            $currentUser->setFullName($this->buildFullName($firstName, $lastName, $email));
        }

        $this->entityManager->flush();
    }

    private function buildFullName(?string $firstName, ?string $lastName, ?string $email): string
    {
        $candidate = trim(implode(' ', array_filter([$firstName, $lastName])));

        if ('' !== $candidate) {
            return $candidate;
        }

        if (null !== $email && '' !== $email) {
            return ucfirst((string) preg_replace('/[._-]+/', ' ', strstr($email, '@', true) ?: $email));
        }

        return 'Client Expédition Mystère';
    }

    private function normalizeEmail(?string $email): ?string
    {
        $normalized = $this->normalizeNullableString($email);

        return null === $normalized ? null : mb_strtolower($normalized);
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return '' === $normalized ? null : $normalized;
    }

    private function extractGoogleAvatarUrl(object $oauthUser): ?string
    {
        if (\method_exists($oauthUser, 'getAvatar')) {
            return $this->normalizeAvatarUrl($oauthUser->getAvatar());
        }

        return null;
    }

    private function normalizeAvatarUrl(?string $avatarUrl): ?string
    {
        $normalized = $this->normalizeNullableString($avatarUrl);

        if (null === $normalized) {
            return null;
        }

        if (!str_starts_with($normalized, 'https://') && !str_starts_with($normalized, 'http://')) {
            return null;
        }

        return $normalized;
    }
}
