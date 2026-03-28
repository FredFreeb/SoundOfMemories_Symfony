<?php

namespace App\Entity;

use App\Repository\PressMentionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PressMentionRepository::class)]
// Fred note: Je stocke ici les avis presse et retours externes affiches sur la home avec leur photo.
class PressMention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $authorName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $sourceLabel = null;

    #[ORM\Column(type: 'text')]
    private ?string $quotePrimary = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $quoteSecondary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isPublished = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->authorName ?? 'Avis';
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): static
    {
        $this->authorName = $authorName;
        $this->touch();

        return $this;
    }

    public function getSourceLabel(): ?string
    {
        return $this->sourceLabel;
    }

    public function setSourceLabel(?string $sourceLabel): static
    {
        $this->sourceLabel = $sourceLabel;
        $this->touch();

        return $this;
    }

    public function getQuotePrimary(): ?string
    {
        return $this->quotePrimary;
    }

    public function setQuotePrimary(string $quotePrimary): static
    {
        $this->quotePrimary = $quotePrimary;
        $this->touch();

        return $this;
    }

    public function getQuoteSecondary(): ?string
    {
        return $this->quoteSecondary;
    }

    public function setQuoteSecondary(?string $quoteSecondary): static
    {
        $this->quoteSecondary = $quoteSecondary;
        $this->touch();

        return $this;
    }

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(?string $linkUrl): static
    {
        $this->linkUrl = $linkUrl;
        $this->touch();

        return $this;
    }

    public function getLinkLabel(): ?string
    {
        return $this->linkLabel;
    }

    public function setLinkLabel(?string $linkLabel): static
    {
        $this->linkLabel = $linkLabel;
        $this->touch();

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        $this->touch();

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        $this->touch();

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
