<?php

namespace App\Entity;

use App\Repository\EditorialModuleItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EditorialModuleItemRepository::class)]
class EditorialModuleItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EditorialModule $module = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $eyebrow = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bodyText = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $metaPrimary = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $metaSecondary = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $linkLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkUrl = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
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
        return $this->title ?: 'Élément éditorial';
    }

    public function getModule(): ?EditorialModule
    {
        return $this->module;
    }

    public function setModule(?EditorialModule $module): static
    {
        $this->module = $module;
        $this->touch();

        return $this;
    }

    public function getEyebrow(): ?string
    {
        return $this->eyebrow;
    }

    public function setEyebrow(?string $eyebrow): static
    {
        $this->eyebrow = $eyebrow;
        $this->touch();

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        $this->touch();

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;
        $this->touch();

        return $this;
    }

    public function getBodyText(): ?string
    {
        return $this->bodyText;
    }

    public function setBodyText(?string $bodyText): static
    {
        $this->bodyText = $bodyText;
        $this->touch();

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;
        $this->touch();

        return $this;
    }

    public function getMetaPrimary(): ?string
    {
        return $this->metaPrimary;
    }

    public function setMetaPrimary(?string $metaPrimary): static
    {
        $this->metaPrimary = $metaPrimary;
        $this->touch();

        return $this;
    }

    public function getMetaSecondary(): ?string
    {
        return $this->metaSecondary;
    }

    public function setMetaSecondary(?string $metaSecondary): static
    {
        $this->metaSecondary = $metaSecondary;
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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position ?? 0;
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
