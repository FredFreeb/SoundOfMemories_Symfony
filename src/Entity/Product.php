<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
// Fred note: Le produit concentre les infos affichees au client et gerees dans le back-office.
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $name = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $catalogExcerpt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $variantChoiceLabel = null;

    #[ORM\Column]
    private int $priceCents = 0;

    #[ORM\Column]
    private int $stock = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $offerBannerImage = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $animationKey = null;

    #[ORM\Column]
    private bool $isMonthlyOffer = false;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $offerBannerEyebrow = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $offerBannerTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $offerBannerText = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $offerBannerPriceBefore = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $offerBannerPriceAfter = null;

    #[ORM\Column(nullable: true)]
    private ?int $readingLevel = null;

    #[ORM\Column(nullable: true)]
    private ?int $difficultyLevel = null;

    #[ORM\Column(nullable: true)]
    private ?int $maturityLevel = null;

    #[ORM\Column(nullable: true)]
    private ?int $ambianceLevel = null;

    #[ORM\Column]
    private bool $isPublished = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Category $category = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductGalleryImage::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $galleryImages;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductVariant::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $variants;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        // Fred note: On initialise les dates ici pour eviter d'oublier un timestamp lors de la creation.
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->galleryImages = new ArrayCollection();
        $this->variants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // Fred note: EasyAdmin affiche ce texte quand il a besoin d'un nom de produit sans template custom.
    public function __toString(): string
    {
        return $this->name ?? 'Produit';
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getVariantChoiceLabel(): string
    {
        $label = trim((string) $this->variantChoiceLabel);

        return '' !== $label ? $label : 'Taille';
    }

    public function setVariantChoiceLabel(?string $variantChoiceLabel): static
    {
        $this->variantChoiceLabel = $variantChoiceLabel;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCatalogExcerpt(): ?string
    {
        return $this->catalogExcerpt;
    }

    public function setCatalogExcerpt(?string $catalogExcerpt): static
    {
        $this->catalogExcerpt = $catalogExcerpt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents): static
    {
        $this->priceCents = $priceCents;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getAnimationKey(): ?string
    {
        return $this->animationKey;
    }

    public function setAnimationKey(?string $animationKey): static
    {
        $this->animationKey = $animationKey;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOfferBannerImage(): ?string
    {
        return $this->offerBannerImage;
    }

    public function setOfferBannerImage(?string $offerBannerImage): static
    {
        $this->offerBannerImage = $offerBannerImage;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isMonthlyOffer(): bool
    {
        return $this->isMonthlyOffer;
    }

    public function setIsMonthlyOffer(bool $isMonthlyOffer): static
    {
        $this->isMonthlyOffer = $isMonthlyOffer;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOfferBannerEyebrow(): ?string
    {
        return $this->offerBannerEyebrow;
    }

    public function setOfferBannerEyebrow(?string $offerBannerEyebrow): static
    {
        $this->offerBannerEyebrow = $offerBannerEyebrow;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOfferBannerTitle(): ?string
    {
        return $this->offerBannerTitle;
    }

    public function setOfferBannerTitle(?string $offerBannerTitle): static
    {
        $this->offerBannerTitle = $offerBannerTitle;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOfferBannerText(): ?string
    {
        return $this->offerBannerText;
    }

    public function setOfferBannerText(?string $offerBannerText): static
    {
        $this->offerBannerText = $offerBannerText;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOfferBannerPriceBefore(): ?string
    {
        return $this->offerBannerPriceBefore;
    }

    public function setOfferBannerPriceBefore(?string $offerBannerPriceBefore): static
    {
        $this->offerBannerPriceBefore = $offerBannerPriceBefore;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOfferBannerPriceAfter(): ?string
    {
        return $this->offerBannerPriceAfter;
    }

    public function setOfferBannerPriceAfter(?string $offerBannerPriceAfter): static
    {
        $this->offerBannerPriceAfter = $offerBannerPriceAfter;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getReadingLevel(): ?int
    {
        return $this->readingLevel;
    }

    public function setReadingLevel(?int $readingLevel): static
    {
        $this->readingLevel = $this->sanitizeLevel($readingLevel);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDifficultyLevel(): ?int
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(?int $difficultyLevel): static
    {
        $this->difficultyLevel = $this->sanitizeLevel($difficultyLevel);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getMaturityLevel(): ?int
    {
        return $this->maturityLevel;
    }

    public function setMaturityLevel(?int $maturityLevel): static
    {
        $this->maturityLevel = $this->sanitizeLevel($maturityLevel);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getAmbianceLevel(): ?int
    {
        return $this->ambianceLevel;
    }

    public function setAmbianceLevel(?int $ambianceLevel): static
    {
        $this->ambianceLevel = $this->sanitizeLevel($ambianceLevel);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        $this->updatedAt = new \DateTimeImmutable();

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, ProductGalleryImage>
     */
    public function getGalleryImages(): Collection
    {
        return $this->galleryImages;
    }

    public function addGalleryImage(ProductGalleryImage $galleryImage): static
    {
        if (!$this->galleryImages->contains($galleryImage)) {
            $this->galleryImages->add($galleryImage);
            $galleryImage->setProduct($this);
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function removeGalleryImage(ProductGalleryImage $galleryImage): static
    {
        if ($this->galleryImages->removeElement($galleryImage)) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(ProductVariant $variant): static
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setProduct($this);
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function removeVariant(ProductVariant $variant): static
    {
        if ($this->variants->removeElement($variant)) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return list<ProductVariant>
     */
    public function getPublishedVariants(): array
    {
        return array_values(array_filter(
            $this->variants->toArray(),
            static fn (ProductVariant $variant): bool => $variant->isPublished(),
        ));
    }

    public function hasVariants(): bool
    {
        return [] !== $this->getPublishedVariants();
    }

    public function getDefaultVariant(): ?ProductVariant
    {
        $variants = $this->getPublishedVariants();

        foreach ($variants as $variant) {
            if ($variant->isDefault()) {
                return $variant;
            }
        }

        return $variants[0] ?? null;
    }

    public function getStartingPriceCents(): int
    {
        $variants = $this->getPublishedVariants();

        if ([] === $variants) {
            return $this->priceCents;
        }

        return min(array_map(
            static fn (ProductVariant $variant): int => $variant->getPriceCents(),
            $variants,
        ));
    }

    public function getFormattedStartingPrice(): string
    {
        return number_format($this->getStartingPriceCents() / 100, 2, ',', ' ') . ' EUR';
    }

    public function getDisplayCompareAtPriceCents(): ?int
    {
        $defaultVariant = $this->getDefaultVariant();

        return $defaultVariant?->getCompareAtPriceCents();
    }

    public function getFormattedDisplayCompareAtPrice(): ?string
    {
        $compareAtPriceCents = $this->getDisplayCompareAtPriceCents();

        if (null === $compareAtPriceCents || $compareAtPriceCents <= 0) {
            return null;
        }

        return number_format($compareAtPriceCents / 100, 2, ',', ' ') . ' EUR';
    }

    public function getDisplayStock(): int
    {
        $variants = $this->getPublishedVariants();

        if ([] === $variants) {
            return $this->stock;
        }

        return array_reduce(
            $variants,
            static fn (int $carry, ProductVariant $variant): int => $carry + max(0, $variant->getStock()),
            0,
        );
    }

    public function isAvailable(): bool
    {
        return $this->isPublished && $this->getDisplayStock() > 0;
    }

    public function getFormattedPrice(): string
    {
        // Fred note: Le prix est stocke en centimes mais affiche en euros cote interface.
        return number_format($this->priceCents / 100, 2, ',', ' ') . ' EUR';
    }

    public function getAnimationClassName(): string
    {
        $key = trim((string) $this->animationKey);

        return '' !== $key ? 'animation-' . $key : 'animation-none';
    }

    private function sanitizeLevel(?int $level): ?int
    {
        if (null === $level) {
            return null;
        }

        return max(1, min(5, $level));
    }
}
