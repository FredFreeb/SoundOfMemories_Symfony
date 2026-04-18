<?php

namespace App\Entity;

use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteSettingsRepository::class)]
// Fred note: Je stocke ici les visuels globaux pour que le front puisse se rapprocher du site de reference sans hardcoder les images.
class SiteSettings
{
    public const SECTION_STYLE_TYPE_ONE = 'type-1';
    public const SECTION_STYLE_TYPE_TWO = 'type-2';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $siteName = 'Expéditions Mystérieuses';

    #[ORM\Column(length: 120)]
    private string $presetName = 'Classique';

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $presetKey = 'default';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $tagline = 'Des affaires troubles, des indices physiques et une boutique à l\'ambiance d\'archives secrètes.';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $headerLogo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeHeroBackground = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeHeroVisual = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeHeroSlideOne = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeHeroSlideTwo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeHeroSlideThree = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeHeroSlideFour = null;

    #[ORM\Column(length: 40)]
    private string $homeIntroStylePreset = self::SECTION_STYLE_TYPE_TWO;

    #[ORM\Column(length: 40)]
    private string $homeArchiveCtaStylePreset = self::SECTION_STYLE_TYPE_ONE;

    #[ORM\Column(length: 40)]
    private string $aboutPrimaryStylePreset = self::SECTION_STYLE_TYPE_ONE;

    #[ORM\Column(length: 40)]
    private string $aboutSecondaryStylePreset = self::SECTION_STYLE_TYPE_TWO;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeOverviewImageOne = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeOverviewImageTwo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeOverviewImageThree = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shopHeroBackground = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sectionBackgroundPrimary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sectionBackgroundSecondary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sectionBackgroundTertiary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $soundcloudUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $spotifyUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $appleMusicUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $youtubeMusicUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeHeroTitle = 'Votre cold case à élucider, à vivre chez vous.';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $homeHeroText = 'Recevez une enquête physique, manipulez les indices et plongez dans une ambiance d\'archives secrètes.';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $homeSpecialOfferEyebrow = 'Offre spéciale';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeSpecialOfferTitle = 'Une offre secrète pour prolonger l\'expédition';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $homeSpecialOfferText = 'Profitez d\'un avantage exclusif sur les enquêtes du moment et entrez plus vite dans les archives.';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeSpecialOfferImage = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $homeSpecialOfferPriceBefore = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $homeSpecialOfferPriceAfter = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $homeSpecialOfferButtonLabel = 'Découvrir l\'offre';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeSpecialOfferButtonUrl = '/boutique';

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiteName(): string
    {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): static
    {
        $this->siteName = $siteName;
        $this->touch();

        return $this;
    }

    public function getPresetName(): string
    {
        return $this->presetName;
    }

    public function setPresetName(string $presetName): static
    {
        $this->presetName = $presetName;
        $this->touch();

        return $this;
    }

    public function getPresetKey(): ?string
    {
        return $this->presetKey;
    }

    public function setPresetKey(?string $presetKey): static
    {
        $this->presetKey = $presetKey;
        $this->touch();

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        $this->touch();

        return $this;
    }

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function setTagline(?string $tagline): static
    {
        $this->tagline = $tagline;
        $this->touch();

        return $this;
    }

    public function getHeaderLogo(): ?string
    {
        return $this->headerLogo;
    }

    public function setHeaderLogo(?string $headerLogo): static
    {
        $this->headerLogo = $headerLogo;
        $this->touch();

        return $this;
    }

    public function getHomeHeroBackground(): ?string
    {
        return $this->homeHeroBackground;
    }

    public function setHomeHeroBackground(?string $homeHeroBackground): static
    {
        $this->homeHeroBackground = $homeHeroBackground;
        $this->touch();

        return $this;
    }

    public function getHomeHeroVisual(): ?string
    {
        return $this->homeHeroVisual;
    }

    public function setHomeHeroVisual(?string $homeHeroVisual): static
    {
        $this->homeHeroVisual = $homeHeroVisual;
        $this->touch();

        return $this;
    }

    public function getHomeHeroSlideOne(): ?string
    {
        return $this->homeHeroSlideOne;
    }

    public function setHomeHeroSlideOne(?string $homeHeroSlideOne): static
    {
        $this->homeHeroSlideOne = $homeHeroSlideOne;
        $this->touch();

        return $this;
    }

    public function getHomeHeroSlideTwo(): ?string
    {
        return $this->homeHeroSlideTwo;
    }

    public function setHomeHeroSlideTwo(?string $homeHeroSlideTwo): static
    {
        $this->homeHeroSlideTwo = $homeHeroSlideTwo;
        $this->touch();

        return $this;
    }

    public function getHomeHeroSlideThree(): ?string
    {
        return $this->homeHeroSlideThree;
    }

    public function setHomeHeroSlideThree(?string $homeHeroSlideThree): static
    {
        $this->homeHeroSlideThree = $homeHeroSlideThree;
        $this->touch();

        return $this;
    }

    public function getHomeHeroSlideFour(): ?string
    {
        return $this->homeHeroSlideFour;
    }

    public function setHomeHeroSlideFour(?string $homeHeroSlideFour): static
    {
        $this->homeHeroSlideFour = $homeHeroSlideFour;
        $this->touch();

        return $this;
    }

    public function getHomeIntroStylePreset(): string
    {
        return $this->homeIntroStylePreset;
    }

    public function setHomeIntroStylePreset(string $homeIntroStylePreset): static
    {
        $this->homeIntroStylePreset = $homeIntroStylePreset;
        $this->touch();

        return $this;
    }

    public function getHomeArchiveCtaStylePreset(): string
    {
        return $this->homeArchiveCtaStylePreset;
    }

    public function setHomeArchiveCtaStylePreset(string $homeArchiveCtaStylePreset): static
    {
        $this->homeArchiveCtaStylePreset = $homeArchiveCtaStylePreset;
        $this->touch();

        return $this;
    }

    public function getHomeOverviewImageOne(): ?string
    {
        return $this->homeOverviewImageOne;
    }

    public function setHomeOverviewImageOne(?string $homeOverviewImageOne): static
    {
        $this->homeOverviewImageOne = $homeOverviewImageOne;
        $this->touch();

        return $this;
    }

    public function getHomeOverviewImageTwo(): ?string
    {
        return $this->homeOverviewImageTwo;
    }

    public function setHomeOverviewImageTwo(?string $homeOverviewImageTwo): static
    {
        $this->homeOverviewImageTwo = $homeOverviewImageTwo;
        $this->touch();

        return $this;
    }

    public function getHomeOverviewImageThree(): ?string
    {
        return $this->homeOverviewImageThree;
    }

    public function setHomeOverviewImageThree(?string $homeOverviewImageThree): static
    {
        $this->homeOverviewImageThree = $homeOverviewImageThree;
        $this->touch();

        return $this;
    }

    public function getAboutPrimaryStylePreset(): string
    {
        return $this->aboutPrimaryStylePreset;
    }

    public function setAboutPrimaryStylePreset(string $aboutPrimaryStylePreset): static
    {
        $this->aboutPrimaryStylePreset = $aboutPrimaryStylePreset;
        $this->touch();

        return $this;
    }

    public function getAboutSecondaryStylePreset(): string
    {
        return $this->aboutSecondaryStylePreset;
    }

    public function setAboutSecondaryStylePreset(string $aboutSecondaryStylePreset): static
    {
        $this->aboutSecondaryStylePreset = $aboutSecondaryStylePreset;
        $this->touch();

        return $this;
    }

    public function getShopHeroBackground(): ?string
    {
        return $this->shopHeroBackground;
    }

    public function setShopHeroBackground(?string $shopHeroBackground): static
    {
        $this->shopHeroBackground = $shopHeroBackground;
        $this->touch();

        return $this;
    }

    public function getSectionBackgroundPrimary(): ?string
    {
        return $this->sectionBackgroundPrimary;
    }

    public function setSectionBackgroundPrimary(?string $sectionBackgroundPrimary): static
    {
        $this->sectionBackgroundPrimary = $sectionBackgroundPrimary;
        $this->touch();

        return $this;
    }

    public function getSectionBackgroundSecondary(): ?string
    {
        return $this->sectionBackgroundSecondary;
    }

    public function setSectionBackgroundSecondary(?string $sectionBackgroundSecondary): static
    {
        $this->sectionBackgroundSecondary = $sectionBackgroundSecondary;
        $this->touch();

        return $this;
    }

    public function getSectionBackgroundTertiary(): ?string
    {
        return $this->sectionBackgroundTertiary;
    }

    public function setSectionBackgroundTertiary(?string $sectionBackgroundTertiary): static
    {
        $this->sectionBackgroundTertiary = $sectionBackgroundTertiary;
        $this->touch();

        return $this;
    }

    public function getSoundcloudUrl(): ?string
    {
        return $this->soundcloudUrl;
    }

    public function setSoundcloudUrl(?string $soundcloudUrl): static
    {
        $this->soundcloudUrl = $soundcloudUrl;
        $this->touch();

        return $this;
    }

    public function getSpotifyUrl(): ?string
    {
        return $this->spotifyUrl;
    }

    public function setSpotifyUrl(?string $spotifyUrl): static
    {
        $this->spotifyUrl = $spotifyUrl;
        $this->touch();

        return $this;
    }

    public function getAppleMusicUrl(): ?string
    {
        return $this->appleMusicUrl;
    }

    public function setAppleMusicUrl(?string $appleMusicUrl): static
    {
        $this->appleMusicUrl = $appleMusicUrl;
        $this->touch();

        return $this;
    }

    public function getYoutubeMusicUrl(): ?string
    {
        return $this->youtubeMusicUrl;
    }

    public function setYoutubeMusicUrl(?string $youtubeMusicUrl): static
    {
        $this->youtubeMusicUrl = $youtubeMusicUrl;
        $this->touch();

        return $this;
    }

    public function getHomeHeroTitle(): ?string
    {
        return $this->homeHeroTitle;
    }

    public function setHomeHeroTitle(?string $homeHeroTitle): static
    {
        $this->homeHeroTitle = $homeHeroTitle;
        $this->touch();

        return $this;
    }

    public function getHomeHeroText(): ?string
    {
        return $this->homeHeroText;
    }

    public function setHomeHeroText(?string $homeHeroText): static
    {
        $this->homeHeroText = $homeHeroText;
        $this->touch();

        return $this;
    }

    public function getHomeSpecialOfferEyebrow(): ?string
    {
        return $this->homeSpecialOfferEyebrow;
    }

    public function setHomeSpecialOfferEyebrow(?string $homeSpecialOfferEyebrow): static
    {
        $this->homeSpecialOfferEyebrow = $homeSpecialOfferEyebrow;
        $this->touch();

        return $this;
    }

    public function getHomeSpecialOfferTitle(): ?string
    {
        return $this->homeSpecialOfferTitle;
    }

    public function setHomeSpecialOfferTitle(?string $homeSpecialOfferTitle): static
    {
        $this->homeSpecialOfferTitle = $homeSpecialOfferTitle;
        $this->touch();

        return $this;
    }

    public function getHomeSpecialOfferText(): ?string
    {
        return $this->homeSpecialOfferText;
    }

    public function setHomeSpecialOfferText(?string $homeSpecialOfferText): static
    {
        $this->homeSpecialOfferText = $homeSpecialOfferText;
        $this->touch();

        return $this;
    }

    public function getHomeSpecialOfferImage(): ?string
    {
        return $this->homeSpecialOfferImage;
    }

    public function setHomeSpecialOfferImage(?string $homeSpecialOfferImage): static
    {
        $this->homeSpecialOfferImage = $homeSpecialOfferImage;
        $this->touch();

        return $this;
    }

    public function getHomeSpecialOfferPriceBefore(): ?string
    {
        return $this->homeSpecialOfferPriceBefore;
    }

    public function setHomeSpecialOfferPriceBefore(?string $homeSpecialOfferPriceBefore): static
    {
        $this->homeSpecialOfferPriceBefore = $homeSpecialOfferPriceBefore;
        $this->touch();

        return $this;
    }

    public function getHomeSpecialOfferPriceAfter(): ?string
    {
        return $this->homeSpecialOfferPriceAfter;
    }

    public function setHomeSpecialOfferPriceAfter(?string $homeSpecialOfferPriceAfter): static
    {
        $this->homeSpecialOfferPriceAfter = $homeSpecialOfferPriceAfter;
        $this->touch();

        return $this;
    }

    public function getHomeSpecialOfferButtonLabel(): ?string
    {
        return $this->homeSpecialOfferButtonLabel;
    }

    public function setHomeSpecialOfferButtonLabel(?string $homeSpecialOfferButtonLabel): static
    {
        $this->homeSpecialOfferButtonLabel = $homeSpecialOfferButtonLabel;
        $this->touch();

        return $this;
    }

    public function getHomeSpecialOfferButtonUrl(): ?string
    {
        return $this->homeSpecialOfferButtonUrl;
    }

    public function setHomeSpecialOfferButtonUrl(?string $homeSpecialOfferButtonUrl): static
    {
        $this->homeSpecialOfferButtonUrl = $homeSpecialOfferButtonUrl;
        $this->touch();

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, string>
     */
    public static function getSectionStyleChoices(): array
    {
        return [
            'Type 1 - Appel a l action sombre et premium' => self::SECTION_STYLE_TYPE_ONE,
            'Type 2 - Editorial clair et immersif' => self::SECTION_STYLE_TYPE_TWO,
        ];
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
