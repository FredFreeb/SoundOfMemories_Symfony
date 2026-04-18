<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
// Fred note: J'utilise cette entite pour suivre un achat, les infos client et l'etat de paiement.
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const LEGACY_STATUS_COMPLETED = 'completed';

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_AUTHORIZED = 'authorized';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_CANCELLED = 'cancelled';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    public const DELIVERY_STATUS_PENDING = 'pending';
    public const DELIVERY_STATUS_PREPARING = 'preparing';
    public const DELIVERY_STATUS_LABEL_CREATED = 'label_created';
    public const DELIVERY_STATUS_IN_TRANSIT = 'in_transit';
    public const DELIVERY_STATUS_RECEIVED = 'received';
    public const DELIVERY_STATUS_ISSUE = 'issue';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 180)]
    private string $customerEmail = '';

    #[ORM\Column(length: 180)]
    private string $customerName = '';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $customerPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $shippingCountryCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private int $totalCents = 0;

    #[ORM\Column]
    private int $subtotalCents = 0;

    #[ORM\Column]
    private int $discountCents = 0;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $discountLabel = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $paymentStatus = null;

    #[ORM\Column(length: 40)]
    private string $deliveryStatus = self::DELIVERY_STATUS_PENDING;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $paymentProvider = null;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $shippingCarrier = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $shippingProvider = null;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $shippingMethodCode = null;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $shippingMethodLabel = null;

    #[ORM\Column]
    private int $shippingRateCents = 0;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $molliePaymentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCheckoutSessionId = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $checkoutAccessToken = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $customerAccount = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        // Fred note: Je pars toujours d'une date de creation et d'une collection vide pour garder un etat propre.
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
        $this->checkoutAccessToken = bin2hex(random_bytes(16));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // Fred note: J'affiche ce libelle dans l'admin pour reperer vite une commande sans ouvrir sa fiche.
    public function __toString(): string
    {
        $label = $this->customerName !== '' ? $this->customerName : $this->customerEmail;

        return sprintf('#%s - %s', $this->id ?? 'nouvelle', $label !== '' ? $label : 'Commande');
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (self::LEGACY_STATUS_COMPLETED === $status) {
            $status = self::STATUS_CLOSED;
        }

        $this->status = $status;

        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): static
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

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

    public function getShippingCountryCode(): ?string
    {
        return $this->shippingCountryCode;
    }

    public function setShippingCountryCode(?string $shippingCountryCode): static
    {
        $this->shippingCountryCode = null !== $shippingCountryCode && '' !== trim($shippingCountryCode)
            ? mb_strtoupper(trim($shippingCountryCode))
            : null;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getTotalCents(): int
    {
        return $this->totalCents;
    }

    public function setTotalCents(int $totalCents): static
    {
        $this->totalCents = $totalCents;

        return $this;
    }

    public function getSubtotalCents(): int
    {
        return $this->subtotalCents > 0 ? $this->subtotalCents : $this->totalCents;
    }

    public function setSubtotalCents(int $subtotalCents): static
    {
        $this->subtotalCents = $subtotalCents;

        return $this;
    }

    public function getDiscountCents(): int
    {
        return $this->discountCents;
    }

    public function setDiscountCents(int $discountCents): static
    {
        $this->discountCents = max(0, $discountCents);

        return $this;
    }

    public function getDiscountLabel(): ?string
    {
        return $this->discountLabel;
    }

    public function setDiscountLabel(?string $discountLabel): static
    {
        $this->discountLabel = $discountLabel;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getDeliveryStatus(): string
    {
        return $this->deliveryStatus;
    }

    public function setDeliveryStatus(string $deliveryStatus): static
    {
        $this->deliveryStatus = $deliveryStatus;

        return $this;
    }

    public function getPaymentProvider(): ?string
    {
        return $this->paymentProvider;
    }

    public function setPaymentProvider(?string $paymentProvider): static
    {
        $this->paymentProvider = $paymentProvider;

        return $this;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): static
    {
        $this->paymentReference = $paymentReference;

        return $this;
    }

    public function getShippingCarrier(): ?string
    {
        return $this->shippingCarrier;
    }

    public function setShippingCarrier(?string $shippingCarrier): static
    {
        $this->shippingCarrier = $shippingCarrier;

        return $this;
    }

    public function getShippingProvider(): ?string
    {
        return $this->shippingProvider;
    }

    public function setShippingProvider(?string $shippingProvider): static
    {
        $this->shippingProvider = $shippingProvider;

        return $this;
    }

    public function getShippingMethodCode(): ?string
    {
        return $this->shippingMethodCode;
    }

    public function setShippingMethodCode(?string $shippingMethodCode): static
    {
        $this->shippingMethodCode = $shippingMethodCode;

        return $this;
    }

    public function getShippingMethodLabel(): ?string
    {
        return $this->shippingMethodLabel;
    }

    public function setShippingMethodLabel(?string $shippingMethodLabel): static
    {
        $this->shippingMethodLabel = $shippingMethodLabel;

        return $this;
    }

    public function getShippingRateCents(): int
    {
        return max(0, $this->shippingRateCents);
    }

    public function setShippingRateCents(int $shippingRateCents): static
    {
        $this->shippingRateCents = max(0, $shippingRateCents);

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): static
    {
        $this->trackingUrl = $trackingUrl;

        return $this;
    }

    public function getMolliePaymentId(): ?string
    {
        return $this->molliePaymentId;
    }

    public function setMolliePaymentId(?string $molliePaymentId): static
    {
        $this->molliePaymentId = $molliePaymentId;

        return $this;
    }

    public function getStripeCheckoutSessionId(): ?string
    {
        return $this->stripeCheckoutSessionId;
    }

    public function setStripeCheckoutSessionId(?string $stripeCheckoutSessionId): static
    {
        $this->stripeCheckoutSessionId = $stripeCheckoutSessionId;

        return $this;
    }

    public function getCheckoutAccessToken(): ?string
    {
        return $this->checkoutAccessToken;
    }

    public function setCheckoutAccessToken(?string $checkoutAccessToken): static
    {
        $this->checkoutAccessToken = null !== $checkoutAccessToken && '' !== trim($checkoutAccessToken)
            ? trim($checkoutAccessToken)
            : bin2hex(random_bytes(16));

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getCustomerAccount(): ?User
    {
        return $this->customerAccount;
    }

    public function setCustomerAccount(?User $customerAccount): static
    {
        $this->customerAccount = $customerAccount;

        if ($customerAccount instanceof User) {
            // Fred note: Je reutilise les infos du compte pour eviter une saisie repetee.
            if ('' === trim($this->customerEmail)) {
                $this->customerEmail = $customerAccount->getEmail() ?? '';
            }

            if ('' === trim($this->customerName)) {
                $this->customerName = $customerAccount->getFullName() ?? '';
            }
        }

        return $this;
    }

    public function getFormattedTotal(): string
    {
        // Fred note: Comme pour les produits, le total est converti depuis les centimes pour l'affichage.
        return number_format($this->totalCents / 100, 2, ',', ' ') . ' EUR';
    }

    public function getFormattedSubtotal(): string
    {
        return number_format($this->getSubtotalCents() / 100, 2, ',', ' ') . ' EUR';
    }

    public function getFormattedDiscount(): string
    {
        return number_format($this->discountCents / 100, 2, ',', ' ') . ' EUR';
    }

    public function getFormattedShippingRate(): string
    {
        return number_format($this->getShippingRateCents() / 100, 2, ',', ' ') . ' EUR';
    }

    public function getReferenceLabel(): string
    {
        if (null === $this->id) {
            return 'SOM-NOUVELLE';
        }

        return sprintf('SOM-%s-%04d', $this->createdAt->format('y'), $this->id);
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            // Fred note: On synchronise les deux cotes de la relation Doctrine dans un seul endroit.
            $item->setOrderRef($this);
        }

        return $this;
    }

    public function isPaid(): bool
    {
        return self::PAYMENT_STATUS_PAID === $this->paymentStatus;
    }

    public function hasDiscount(): bool
    {
        return $this->discountCents > 0;
    }

    public function getItemsCount(): int
    {
        return $this->items->count();
    }

    public function getItemsSummary(): string
    {
        $labels = [];

        foreach ($this->items as $item) {
            $labels[] = $item->getProductName();

            if (\count($labels) >= 2) {
                break;
            }
        }

        if ([] === $labels) {
            return 'Aucune ligne';
        }

        if ($this->getItemsCount() > 2) {
            $labels[] = sprintf('+ %d autre%s', $this->getItemsCount() - 2, $this->getItemsCount() - 2 > 1 ? 's' : '');
        }

        return implode(' · ', $labels);
    }

    public function getShippingSummary(): string
    {
        $parts = array_values(array_filter([
            $this->city,
            $this->getShippingCountryCode() ? $this->getShippingCountryLabel() : null,
        ], static fn (?string $part): bool => null !== $part && '' !== trim($part)));

        return [] !== $parts ? implode(', ', $parts) : 'Destination non renseignée';
    }

    public function getStatusLabel(): string
    {
        return self::labelForStatus($this->status);
    }

    public function getPaymentStatusLabel(): string
    {
        return self::labelForPaymentStatus($this->paymentStatus);
    }

    public function getDeliveryStatusLabel(): string
    {
        return self::labelForDeliveryStatus($this->deliveryStatus);
    }

    public function getShippingCountryLabel(): string
    {
        $countryCode = $this->getShippingCountryCode();

        if (null === $countryCode || '' === $countryCode) {
            return 'Pays non renseigné';
        }

        return Countries::getName($countryCode, 'fr') ?? $countryCode;
    }

    public static function getStatusChoices(): array
    {
        return [
            'En attente' => self::STATUS_PENDING,
            'Payée' => self::STATUS_PAID,
            'En préparation' => self::STATUS_PROCESSING,
            'Expédiée' => self::STATUS_SHIPPED,
            'Clôturée' => self::STATUS_CLOSED,
            'Annulée' => self::STATUS_CANCELLED,
            'Remboursée' => self::STATUS_REFUNDED,
        ];
    }

    public static function getPaymentStatusChoices(): array
    {
        return [
            'En attente' => self::PAYMENT_STATUS_PENDING,
            'Autorisé' => self::PAYMENT_STATUS_AUTHORIZED,
            'Payé' => self::PAYMENT_STATUS_PAID,
            'Annulé' => self::PAYMENT_STATUS_CANCELLED,
            'Échoué' => self::PAYMENT_STATUS_FAILED,
            'Remboursé' => self::PAYMENT_STATUS_REFUNDED,
        ];
    }

    public static function getDeliveryStatusChoices(): array
    {
        return [
            'Non lancée' => self::DELIVERY_STATUS_PENDING,
            'Préparation colis' => self::DELIVERY_STATUS_PREPARING,
            'Étiquette créée' => self::DELIVERY_STATUS_LABEL_CREATED,
            'En transit' => self::DELIVERY_STATUS_IN_TRANSIT,
            'Reçue par le client' => self::DELIVERY_STATUS_RECEIVED,
            'Incident livraison' => self::DELIVERY_STATUS_ISSUE,
        ];
    }

    public static function labelForStatus(?string $status): string
    {
        return self::getStatusChoicesFlipped()[$status ?? ''] ?? ($status ?: 'Non renseigné');
    }

    public static function labelForPaymentStatus(?string $status): string
    {
        return self::getPaymentStatusChoicesFlipped()[$status ?? ''] ?? ($status ?: 'Non renseigné');
    }

    public static function labelForDeliveryStatus(?string $status): string
    {
        return self::getDeliveryStatusChoicesFlipped()[$status ?? ''] ?? ($status ?: 'Non renseigné');
    }

    private static function getStatusChoicesFlipped(): array
    {
        return array_flip(self::getStatusChoices());
    }

    private static function getPaymentStatusChoicesFlipped(): array
    {
        return array_flip(self::getPaymentStatusChoices());
    }

    private static function getDeliveryStatusChoicesFlipped(): array
    {
        return array_flip(self::getDeliveryStatusChoices());
    }
}
