<?php

namespace App\Entity;

use App\Repository\ProductVariantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductVariantRepository::class)]
// Fred note: Chaque variante represente une taille, un format ou une edition concrete vendue pour un produit.
class ProductVariant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $label = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $sku = null;

    #[ORM\Column]
    private int $priceCents = 0;

    #[ORM\Column(nullable: true)]
    private ?int $compareAtPriceCents = null;

    #[ORM\Column]
    private int $stock = 0;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column]
    private bool $isPublished = true;

    #[ORM\ManyToOne(inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return '' !== trim($this->label) ? $this->label : ($this->sku ?: 'Variante');
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents): static
    {
        $this->priceCents = max(0, $priceCents);

        return $this;
    }

    public function getCompareAtPriceCents(): ?int
    {
        return $this->compareAtPriceCents;
    }

    public function setCompareAtPriceCents(?int $compareAtPriceCents): static
    {
        $this->compareAtPriceCents = null !== $compareAtPriceCents && $compareAtPriceCents > 0
            ? $compareAtPriceCents
            : null;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = max(0, $stock);

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position ?? 0;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function hasPromotion(): bool
    {
        return null !== $this->compareAtPriceCents && $this->compareAtPriceCents > $this->priceCents;
    }

    public function isAvailable(): bool
    {
        return $this->isPublished && $this->stock > 0;
    }

    public function getFormattedPrice(): string
    {
        return number_format($this->priceCents / 100, 2, ',', ' ') . ' EUR';
    }

    public function getFormattedCompareAtPrice(): ?string
    {
        if (null === $this->compareAtPriceCents || $this->compareAtPriceCents <= 0) {
            return null;
        }

        return number_format($this->compareAtPriceCents / 100, 2, ',', ' ') . ' EUR';
    }
}
