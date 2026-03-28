<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Concert;
use App\Entity\Product;
use App\Entity\SiteSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-demo-catalog',
    description: 'Charge un jeu de donnees de demonstration Sound Of Memories pour le merch, les concerts et l’identité du site.',
)]
class LoadDemoCatalogCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $categories = [];
        foreach ($this->getCategoryData() as $item) {
            $category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $item['slug']]) ?? new Category();

            $category
                ->setName($item['name'])
                ->setSlug($item['slug'])
                ->setDescription($item['description']);

            $this->entityManager->persist($category);
            $categories[$item['slug']] = $category;
        }

        foreach ($this->getProductData() as $item) {
            $product = $this->entityManager->getRepository(Product::class)->findOneBy(['slug' => $item['slug']]) ?? new Product();

            $product
                ->setName($item['name'])
                ->setSlug($item['slug'])
                ->setShortDescription($item['shortDescription'])
                ->setCatalogExcerpt($item['catalogExcerpt'])
                ->setDescription($item['description'])
                ->setPriceCents($item['priceCents'])
                ->setStock($item['stock'])
                ->setCoverImage($item['coverImage'])
                ->setAnimationKey($item['animationKey'])
                ->setIsMonthlyOffer($item['isMonthlyOffer'])
                ->setOfferBannerEyebrow($item['offerBannerEyebrow'])
                ->setOfferBannerTitle($item['offerBannerTitle'])
                ->setOfferBannerText($item['offerBannerText'])
                ->setOfferBannerImage($item['offerBannerImage'])
                ->setOfferBannerPriceBefore($item['offerBannerPriceBefore'])
                ->setOfferBannerPriceAfter($item['offerBannerPriceAfter'])
                ->setReadingLevel($item['readingLevel'])
                ->setDifficultyLevel($item['difficultyLevel'])
                ->setMaturityLevel($item['maturityLevel'])
                ->setAmbianceLevel($item['ambianceLevel'])
                ->setIsPublished(true)
                ->setCategory($categories[$item['categorySlug']] ?? null);

            $this->entityManager->persist($product);
        }

        foreach ($this->getConcertData() as $item) {
            $concert = $this->entityManager->getRepository(Concert::class)->findOneBy([
                'title' => $item['title'],
                'concertAt' => $item['concertAt'],
            ]) ?? new Concert();

            $concert
                ->setTitle($item['title'])
                ->setVenue($item['venue'])
                ->setCity($item['city'])
                ->setCountry($item['country'])
                ->setConcertAt($item['concertAt'])
                ->setDetails($item['details'])
                ->setTicketUrl($item['ticketUrl'])
                ->setStatus($item['status'])
                ->setIsHighlighted($item['isHighlighted'])
                ->setIsPublished(true);

            $this->entityManager->persist($concert);
        }

        $settings = $this->entityManager->getRepository(SiteSettings::class)->findCurrent() ?? new SiteSettings();
        $settings
            ->setPresetName('Sound Of Memories')
            ->setPresetKey('default')
            ->setIsActive(true)
            ->setSiteName('Sound Of Memories Fan Base')
            ->setTagline('Fan base officielle non-officielle, merchandising, dates de concert et atmosphère metal moderne.')
            ->setHeaderLogo('/uploads/legacy/Logo.png')
            ->setHomeHeroBackground('/uploads/legacy/htmlwallpaper.jpg')
            ->setHomeHeroVisual('/uploads/legacy/SofM-cover.png')
            ->setHomeHeroTitle('Sound Of Memories reprend la scène.')
            ->setHomeHeroText('Un site fan base mobile-first pour suivre le groupe, retrouver les sorties, acheter le merch et garder l’énergie du live.')
            ->setHomeOverviewImageOne('/uploads/legacy/bannerSand.png')
            ->setHomeOverviewImageTwo('/uploads/legacy/bannerLiving.png')
            ->setHomeOverviewImageThree('/uploads/legacy/bannerToDel.png')
            ->setShopHeroBackground('/uploads/legacy/Sof.jpg');

        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        $io->success('Le catalogue de démonstration Sound Of Memories a été chargé.');

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array{name: string, slug: string, description: string}>
     */
    private function getCategoryData(): array
    {
        return [
            [
                'name' => 'Textile',
                'slug' => 'textile',
                'description' => 'T-shirts, hoodies et pièces à porter au quotidien ou en concert.',
            ],
            [
                'name' => 'Collectors',
                'slug' => 'collectors',
                'description' => 'Objets et éditions qui prolongent l’univers du groupe.',
            ],
            [
                'name' => 'Accessoires',
                'slug' => 'accessoires',
                'description' => 'Petites pièces fan base à glisser dans un setup ou un cadeau.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getProductData(): array
    {
        return [
            [
                'name' => 'T-shirt Sound Of Memories',
                'slug' => 'tshirt-sound-of-memories',
                'shortDescription' => 'Le visuel iconique du groupe en version textile fan base.',
                'catalogExcerpt' => 'Un t-shirt live-ready avec le logo et une coupe simple à porter partout.',
                'description' => "Pièce essentielle du merch Sound Of Memories. Idéal pour garder l'identité visuelle du groupe au premier plan, avec un rendu lisible sur scène comme en ville.",
                'priceCents' => 2500,
                'stock' => 42,
                'coverImage' => '/uploads/legacy/Tee-shirtSOM.png',
                'animationKey' => 'vinyl',
                'isMonthlyOffer' => true,
                'offerBannerEyebrow' => 'Édition fan base',
                'offerBannerTitle' => 'Le classique qui lance la nouvelle vitrine',
                'offerBannerText' => 'Le meilleur point d’entrée pour relancer le merch avec une pièce immédiatement identifiable.',
                'offerBannerImage' => '/uploads/legacy/vueTShirt.png',
                'offerBannerPriceBefore' => '29 EUR',
                'offerBannerPriceAfter' => '25 EUR',
                'readingLevel' => 2,
                'difficultyLevel' => 1,
                'maturityLevel' => 2,
                'ambianceLevel' => 4,
                'categorySlug' => 'textile',
            ],
            [
                'name' => 'Montre Collector SOM',
                'slug' => 'montre-collector-som',
                'shortDescription' => 'Une pièce collector pensée comme souvenir premium du groupe.',
                'catalogExcerpt' => 'Objet fan base plus premium pour les fans qui veulent une pièce marquante.',
                'description' => "Cette montre collector s’adresse aux fans qui veulent un objet moins classique que le textile, avec une présence forte dans l’univers de la fan base.",
                'priceCents' => 14000,
                'stock' => 8,
                'coverImage' => '/uploads/legacy/SOFWatch.jpg',
                'animationKey' => 'pulse',
                'isMonthlyOffer' => false,
                'offerBannerEyebrow' => null,
                'offerBannerTitle' => null,
                'offerBannerText' => null,
                'offerBannerImage' => null,
                'offerBannerPriceBefore' => null,
                'offerBannerPriceAfter' => null,
                'readingLevel' => 4,
                'difficultyLevel' => 2,
                'maturityLevel' => 5,
                'ambianceLevel' => 3,
                'categorySlug' => 'collectors',
            ],
            [
                'name' => 'Sneakers Fan Base',
                'slug' => 'sneakers-fan-base',
                'shortDescription' => 'Une paire marquante pour prolonger l’esthétique du groupe.',
                'catalogExcerpt' => 'Accessoire de fan base très visuel, entre lifestyle et pièce de scène.',
                'description' => "Ces sneakers prolongent l'univers Sound Of Memories dans un registre plus lifestyle, avec une présence visuelle plus audacieuse que les pièces de merch classiques.",
                'priceCents' => 8900,
                'stock' => 14,
                'coverImage' => '/uploads/legacy/SofShoes2.jpg',
                'animationKey' => 'embers',
                'isMonthlyOffer' => false,
                'offerBannerEyebrow' => null,
                'offerBannerTitle' => null,
                'offerBannerText' => null,
                'offerBannerImage' => null,
                'offerBannerPriceBefore' => null,
                'offerBannerPriceAfter' => null,
                'readingLevel' => 3,
                'difficultyLevel' => 2,
                'maturityLevel' => 3,
                'ambianceLevel' => 5,
                'categorySlug' => 'accessoires',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getConcertData(): array
    {
        return [
            [
                'title' => 'Release Night: The Sand Within',
                'venue' => 'La Machine du Moulin Rouge',
                'city' => 'Paris',
                'country' => 'France',
                'concertAt' => new \DateTimeImmutable('2026-06-19 20:00:00'),
                'details' => 'Soirée fan base avec première partie, merch corner et rencontre après le set.',
                'ticketUrl' => 'https://example.com/tickets/paris',
                'status' => Concert::STATUS_ANNOUNCED,
                'isHighlighted' => true,
            ],
            [
                'title' => 'Metal Echoes Festival',
                'venue' => 'Le Transbordeur',
                'city' => 'Lyon',
                'country' => 'France',
                'concertAt' => new \DateTimeImmutable('2026-08-07 19:30:00'),
                'details' => 'Pass festival disponible, arrivée conseillée avant 18h30.',
                'ticketUrl' => 'https://example.com/tickets/lyon',
                'status' => Concert::STATUS_ANNOUNCED,
                'isHighlighted' => false,
            ],
            [
                'title' => 'Autumn Circle',
                'venue' => 'Rock Café',
                'city' => 'Prague',
                'country' => 'République tchèque',
                'concertAt' => new \DateTimeImmutable('2026-10-24 20:30:00'),
                'details' => 'Date club plus intimiste, stock merch limité sur place.',
                'ticketUrl' => 'https://example.com/tickets/prague',
                'status' => Concert::STATUS_SOLD_OUT,
                'isHighlighted' => false,
            ],
        ];
    }
}
