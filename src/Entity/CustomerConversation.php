<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
// Fred note: J'unifie ici les questions avant achat et les discussions SAV pour garder une seule logique de conversation.
class CustomerConversation
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_WAITING_CUSTOMER = 'waiting_customer';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private string $type = 'pre_sale';

    #[ORM\Column(length: 180)]
    private string $subject = '';

    #[ORM\Column(length: 180)]
    private string $customerName = '';

    #[ORM\Column(length: 180)]
    private string $customerEmail = '';

    #[ORM\Column(length: 40)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column]
    private bool $hasUnreadForAdmin = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $lastMessageAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $customerAccount = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Order $orderRef = null;

    /**
     * @var Collection<int, CustomerConversationMessage>
     */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: CustomerConversationMessage::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastMessageAt = new \DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->subject !== '' ? $this->subject : 'Conversation client';
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

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

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = mb_strtolower($customerEmail);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function hasUnreadForAdmin(): bool
    {
        return $this->hasUnreadForAdmin;
    }

    public function setHasUnreadForAdmin(bool $hasUnreadForAdmin): static
    {
        $this->hasUnreadForAdmin = $hasUnreadForAdmin;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastMessageAt(): \DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(\DateTimeImmutable $lastMessageAt): static
    {
        $this->lastMessageAt = $lastMessageAt;

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
            if ('' === $this->customerEmail) {
                $this->customerEmail = $customerAccount->getEmail() ?? '';
            }

            if ('' === $this->customerName) {
                $this->customerName = $customerAccount->getFullName() ?? '';
            }
        }

        return $this;
    }

    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(?Order $orderRef): static
    {
        $this->orderRef = $orderRef;

        if ($orderRef instanceof Order) {
            if ('' === $this->customerEmail) {
                $this->customerEmail = $orderRef->getCustomerEmail();
            }

            if ('' === $this->customerName) {
                $this->customerName = $orderRef->getCustomerName();
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CustomerConversationMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(CustomerConversationMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
            $this->lastMessageAt = $message->getCreatedAt();
            $this->hasUnreadForAdmin = 'client' === $message->getAuthorType();
        }

        return $this;
    }

    public function removeMessage(CustomerConversationMessage $message): static
    {
        $this->messages->removeElement($message);

        return $this;
    }

    public function getInboxLabel(): string
    {
        return $this->hasUnreadForAdmin ? 'Nouveau message' : 'Conversation lue';
    }

    /**
     * @return array<string, string>
     */
    public static function getStatusChoices(): array
    {
        return [
            'Ouverte' => self::STATUS_OPEN,
            'En traitement' => self::STATUS_PENDING,
            'En attente du client' => self::STATUS_WAITING_CUSTOMER,
            'Résolue' => self::STATUS_RESOLVED,
            'Fermée' => self::STATUS_CLOSED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getStatusBadgeMap(): array
    {
        return [
            self::STATUS_OPEN => 'warning',
            self::STATUS_PENDING => 'primary',
            self::STATUS_WAITING_CUSTOMER => 'info',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_CLOSED => 'secondary',
        ];
    }

    public static function labelForStatus(?string $status): string
    {
        return array_flip(self::getStatusChoices())[$status ?? ''] ?? ($status ?: 'Non renseigné');
    }

    public function getStatusLabel(): string
    {
        return self::labelForStatus($this->status);
    }
}
