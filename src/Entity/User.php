<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
// Fred note: Cette entite represente les comptes du site, clients comme administrateurs.
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 120)]
    private ?string $fullName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarPath = null;

    #[ORM\Column(length: 191, unique: true, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleAvatarUrl = null;

    #[ORM\Column(length: 191, unique: true, nullable: true)]
    private ?string $appleId = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $defaultAddress = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $addressBuilding = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $addressExtra = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $marketingOptIn = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $marketingConsentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $marketingRevokedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $welcomeDiscountUsedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $accountClosedAt = null;

    // Fred note: Ce champ n'est pas stocke en base; il sert juste pendant les formulaires admin.
    private ?string $plainPassword = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(mappedBy: 'customerAccount', targetEntity: Order::class)]
    private Collection $orders;

    public function __construct()
    {
        // Fred note: Je prepare déjà la collection des commandes pour l'espace client.
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // Fred note: EasyAdmin et Symfony utilisent ce texte quand ils ont besoin d'un libelle utilisateur.
    public function __toString(): string
    {
        return $this->fullName ?? $this->email ?? 'Utilisateur';
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower($email);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Fred note: Symfony attend toujours au minimum ROLE_USER pour un compte connecte.
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Fred note: On nettoie le mot de passe en clair une fois qu'il n'est plus utile.
        $this->plainPassword = null;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): static
    {
        $this->avatarPath = $avatarPath;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getGoogleAvatarUrl(): ?string
    {
        return $this->googleAvatarUrl;
    }

    public function setGoogleAvatarUrl(?string $googleAvatarUrl): static
    {
        $this->googleAvatarUrl = $googleAvatarUrl;

        return $this;
    }

    public function getAppleId(): ?string
    {
        return $this->appleId;
    }

    public function setAppleId(?string $appleId): static
    {
        $this->appleId = $appleId;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        $this->verifiedAt = $isVerified ? ($this->verifiedAt ?? new \DateTimeImmutable()) : null;

        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;
        $this->isVerified = null !== $verifiedAt;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getDefaultAddress(): ?string
    {
        return $this->defaultAddress;
    }

    public function setDefaultAddress(?string $defaultAddress): static
    {
        $this->defaultAddress = $defaultAddress;

        return $this;
    }

    public function getAddressBuilding(): ?string
    {
        return $this->addressBuilding;
    }

    public function setAddressBuilding(?string $addressBuilding): static
    {
        $this->addressBuilding = $addressBuilding;

        return $this;
    }

    public function getAddressExtra(): ?string
    {
        return $this->addressExtra;
    }

    public function setAddressExtra(?string $addressExtra): static
    {
        $this->addressExtra = $addressExtra;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function isMarketingOptIn(): bool
    {
        return $this->marketingOptIn;
    }

    public function setMarketingOptIn(bool $marketingOptIn): static
    {
        if ($marketingOptIn) {
            if (!$this->marketingOptIn) {
                $this->marketingConsentAt ??= new \DateTimeImmutable();
            }

            $this->marketingRevokedAt = null;
        } elseif ($this->marketingOptIn) {
            $this->marketingRevokedAt = new \DateTimeImmutable();
        }

        $this->marketingOptIn = $marketingOptIn;

        return $this;
    }

    public function getMarketingConsentAt(): ?\DateTimeImmutable
    {
        return $this->marketingConsentAt;
    }

    public function setMarketingConsentAt(?\DateTimeImmutable $marketingConsentAt): static
    {
        $this->marketingConsentAt = $marketingConsentAt;

        if (null !== $marketingConsentAt) {
            $this->marketingOptIn = true;
            $this->marketingRevokedAt = null;
        }

        return $this;
    }

    public function getMarketingRevokedAt(): ?\DateTimeImmutable
    {
        return $this->marketingRevokedAt;
    }

    public function setMarketingRevokedAt(?\DateTimeImmutable $marketingRevokedAt): static
    {
        $this->marketingRevokedAt = $marketingRevokedAt;

        if (null !== $marketingRevokedAt) {
            $this->marketingOptIn = false;
        }

        return $this;
    }

    public function getWelcomeDiscountUsedAt(): ?\DateTimeImmutable
    {
        return $this->welcomeDiscountUsedAt;
    }

    public function setWelcomeDiscountUsedAt(?\DateTimeImmutable $welcomeDiscountUsedAt): static
    {
        $this->welcomeDiscountUsedAt = $welcomeDiscountUsedAt;

        return $this;
    }

    public function getAccountClosedAt(): ?\DateTimeImmutable
    {
        return $this->accountClosedAt;
    }

    public function setAccountClosedAt(?\DateTimeImmutable $accountClosedAt): static
    {
        $this->accountClosedAt = $accountClosedAt;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getRoleSummary(): string
    {
        // Fred note: Ce resume est pratique pour lister le niveau d'acces dans EasyAdmin.
        return \in_array('ROLE_ADMIN', $this->getRoles(), true) ? 'Administrateur' : 'Client';
    }

    public function isAdmin(): bool
    {
        return \in_array('ROLE_ADMIN', $this->getRoles(), true);
    }

    public function getCustomerSummary(): string
    {
        // Fred note: Ce resume m'aide a voir vite si le client a complete son profil.
        $parts = array_filter([$this->city, $this->phone]);

        return $parts !== [] ? implode(' • ', $parts) : 'Profil client simple';
    }

    public function getLoginMethodsSummary(): string
    {
        $methods = [];

        if (null !== $this->password && '' !== $this->password) {
            $methods[] = 'Compte local';
        }

        if (null !== $this->googleId && '' !== $this->googleId) {
            $methods[] = 'Google';
        }

        if (null !== $this->appleId && '' !== $this->appleId) {
            $methods[] = 'Apple';
        }

        return $methods !== [] ? implode(' • ', $methods) : 'Aucune méthode active';
    }

    public function hasUsedWelcomeDiscount(): bool
    {
        return null !== $this->welcomeDiscountUsedAt;
    }

    public function isAccountClosed(): bool
    {
        return null !== $this->accountClosedAt;
    }

    public function getMarketingStatusLabel(): string
    {
        return $this->marketingOptIn ? 'Mailing actif' : 'Mailing désactivé';
    }

    public function getWelcomeOfferStatusLabel(): string
    {
        if ($this->hasUsedWelcomeDiscount()) {
            return 'Bienvenue utilisée';
        }

        return $this->marketingOptIn ? 'Bienvenue disponible' : 'Bienvenue inactive';
    }

    public function getAccountStateLabel(): string
    {
        return $this->isAccountClosed() ? 'Compte clôturé' : 'Compte actif';
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            // Fred note: Je synchronise la relation des deux cotes dans un seul endroit.
            $order->setCustomerAccount($this);
        }

        return $this;
    }
}
