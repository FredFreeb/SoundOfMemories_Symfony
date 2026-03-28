<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
// Fred note: Chaque message reste simple pour garder un historique lisible dans l'admin avant de brancher un vrai chat temps reel.
class CustomerConversationMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CustomerConversation $conversation = null;

    #[ORM\Column(length: 20)]
    private string $authorType = 'admin';

    #[ORM\Column(length: 120)]
    private string $authorName = '';

    #[ORM\Column(type: 'text')]
    private string $body = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->authorName !== '' ? $this->authorName : 'Message', $this->createdAt->format('d/m H:i'));
    }

    public function getConversation(): ?CustomerConversation
    {
        return $this->conversation;
    }

    public function setConversation(?CustomerConversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getAuthorType(): string
    {
        return $this->authorType;
    }

    public function setAuthorType(string $authorType): static
    {
        $this->authorType = $authorType;

        return $this;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): static
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

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
}
