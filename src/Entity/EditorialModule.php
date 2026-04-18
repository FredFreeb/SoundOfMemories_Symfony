<?php

namespace App\Entity;

use App\Repository\EditorialModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EditorialModuleRepository::class)]
class EditorialModule
{
    public const PAGE_ABOUT = 'about';
    public const PAGE_HOME = 'home';
    public const PAGE_GALLERY = 'gallery';
    public const PAGE_CONCERTS = 'concerts';
    public const PAGE_SHOP = 'shop';

    public const TYPE_MANIFESTO_HERO = 'manifesto_hero';
    public const TYPE_STORY_BLOCK = 'story_block';
    public const TYPE_LINEUP_GRID = 'lineup_grid';
    public const TYPE_RELEASE_GRID = 'release_grid';
    public const TYPE_PRESS_CLIPPINGS = 'press_clippings';
    public const TYPE_CONCERT_CHRONICLE = 'concert_chronicle';
    public const TYPE_MERCH_STORY_BLOCK = 'merch_story_block';

    public const TONE_EMBER = 'ember';
    public const TONE_FOREST = 'forest';
    public const TONE_OCEAN = 'ocean';
    public const TONE_SAND = 'sand';
    public const TONE_ASH = 'ash';

    public const LAYOUT_SPLIT = 'split';
    public const LAYOUT_CENTERED = 'centered';
    public const LAYOUT_POSTER = 'poster';
    public const LAYOUT_STRIP = 'strip';

    public const BACKGROUND_NONE = 'section-bg-none';
    public const BACKGROUND_ONE = 'section-bg-1';
    public const BACKGROUND_TWO = 'section-bg-2';
    public const BACKGROUND_THREE = 'section-bg-3';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $pageKey = self::PAGE_ABOUT;

    #[ORM\Column(length: 64)]
    private string $sectionKey = 'module';

    #[ORM\Column(length: 64)]
    private string $moduleType = self::TYPE_STORY_BLOCK;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $eyebrow = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $leadText = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bodyText = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $backgroundImagePath = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $metaPrimary = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $metaSecondary = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $metaTertiary = null;

    #[ORM\Column(length: 32)]
    private string $accentTone = self::TONE_EMBER;

    #[ORM\Column(length: 32)]
    private string $layoutPreset = self::LAYOUT_SPLIT;

    #[ORM\Column(length: 32)]
    private string $backgroundSlot = self::BACKGROUND_NONE;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $ctaLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ctaUrl = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isPublished = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, EditorialModuleItem>
     */
    #[ORM\OneToMany(mappedBy: 'module', targetEntity: EditorialModuleItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'createdAt' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public static function getPageChoices(): array
    {
        return [
            'Le groupe' => self::PAGE_ABOUT,
            'Accueil' => self::PAGE_HOME,
            'Gallery' => self::PAGE_GALLERY,
            'Concerts' => self::PAGE_CONCERTS,
            'Boutique' => self::PAGE_SHOP,
        ];
    }

    public static function getModuleTypeChoices(): array
    {
        return [
            'Hero manifeste' => self::TYPE_MANIFESTO_HERO,
            'Bloc histoire' => self::TYPE_STORY_BLOCK,
            'Grille line-up' => self::TYPE_LINEUP_GRID,
            'Grille discographie' => self::TYPE_RELEASE_GRID,
            'Revue de presse' => self::TYPE_PRESS_CLIPPINGS,
            'Chronique live' => self::TYPE_CONCERT_CHRONICLE,
            'Bloc merch éditorial' => self::TYPE_MERCH_STORY_BLOCK,
        ];
    }

    public static function getToneChoices(): array
    {
        return [
            'Braise' => self::TONE_EMBER,
            'Forêt' => self::TONE_FOREST,
            'Océan' => self::TONE_OCEAN,
            'Sable' => self::TONE_SAND,
            'Cendre' => self::TONE_ASH,
        ];
    }

    public static function getLayoutChoices(): array
    {
        return [
            'Split' => self::LAYOUT_SPLIT,
            'Centré' => self::LAYOUT_CENTERED,
            'Poster' => self::LAYOUT_POSTER,
            'Strip' => self::LAYOUT_STRIP,
        ];
    }

    public static function getBackgroundChoices(): array
    {
        return [
            'Aucun fond' => self::BACKGROUND_NONE,
            'Fond 1' => self::BACKGROUND_ONE,
            'Fond 2' => self::BACKGROUND_TWO,
            'Fond 3' => self::BACKGROUND_THREE,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return sprintf('%s · %s', $this->getSectionKey(), $this->getTitle());
    }

    public function getPageKey(): string
    {
        return $this->pageKey;
    }

    public function setPageKey(string $pageKey): static
    {
        $this->pageKey = $pageKey;
        $this->touch();

        return $this;
    }

    public function getSectionKey(): string
    {
        return $this->sectionKey;
    }

    public function setSectionKey(string $sectionKey): static
    {
        $this->sectionKey = $sectionKey;
        $this->touch();

        return $this;
    }

    public function getModuleType(): string
    {
        return $this->moduleType;
    }

    public function setModuleType(string $moduleType): static
    {
        $this->moduleType = $moduleType;
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

    public function getLeadText(): ?string
    {
        return $this->leadText;
    }

    public function setLeadText(?string $leadText): static
    {
        $this->leadText = $leadText;
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

    public function getBackgroundImagePath(): ?string
    {
        return $this->backgroundImagePath;
    }

    public function setBackgroundImagePath(?string $backgroundImagePath): static
    {
        $this->backgroundImagePath = $backgroundImagePath;
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

    public function getMetaTertiary(): ?string
    {
        return $this->metaTertiary;
    }

    public function setMetaTertiary(?string $metaTertiary): static
    {
        $this->metaTertiary = $metaTertiary;
        $this->touch();

        return $this;
    }

    public function getAccentTone(): string
    {
        return $this->accentTone;
    }

    public function setAccentTone(string $accentTone): static
    {
        $this->accentTone = $accentTone;
        $this->touch();

        return $this;
    }

    public function getLayoutPreset(): string
    {
        return $this->layoutPreset;
    }

    public function setLayoutPreset(string $layoutPreset): static
    {
        $this->layoutPreset = $layoutPreset;
        $this->touch();

        return $this;
    }

    public function getBackgroundSlot(): string
    {
        return $this->backgroundSlot;
    }

    public function setBackgroundSlot(string $backgroundSlot): static
    {
        $this->backgroundSlot = $backgroundSlot;
        $this->touch();

        return $this;
    }

    public function getCtaLabel(): ?string
    {
        return $this->ctaLabel;
    }

    public function setCtaLabel(?string $ctaLabel): static
    {
        $this->ctaLabel = $ctaLabel;
        $this->touch();

        return $this;
    }

    public function getCtaUrl(): ?string
    {
        return $this->ctaUrl;
    }

    public function setCtaUrl(?string $ctaUrl): static
    {
        $this->ctaUrl = $ctaUrl;
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

    /**
     * @return Collection<int, EditorialModuleItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * @return list<EditorialModuleItem>
     */
    public function getPublishedItems(): array
    {
        $items = array_values(array_filter($this->items->toArray(), static fn (mixed $item): bool => $item instanceof EditorialModuleItem && $item->isPublished()));

        usort($items, static function (EditorialModuleItem $left, EditorialModuleItem $right): int {
            return [$left->getPosition(), $left->getCreatedAt()->getTimestamp()] <=> [$right->getPosition(), $right->getCreatedAt()->getTimestamp()];
        });

        return $items;
    }

    public function addItem(EditorialModuleItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setModule($this);
            $this->touch();
        }

        return $this;
    }

    public function removeItem(EditorialModuleItem $item): static
    {
        if ($this->items->removeElement($item) && $item->getModule() === $this) {
            $item->setModule(null);
            $this->touch();
        }

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
