<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Concert;
use App\Entity\Product;
use App\Entity\ProductVariant;
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
                ->setVariantChoiceLabel($item['variantChoiceLabel'] ?? 'Taille')
                ->setPriceCents($item['priceCents'])
                ->setStock($item['stock'])
                ->setCoverImage($item['coverImage'])
                ->setAnimationKey($item['animationKey'])
                ->setMerchBadge($item['merchBadge'] ?? null)
                ->setMerchBadgeTone($item['merchBadgeTone'] ?? null)
                ->setSortPosition($item['sortPosition'] ?? 0)
                ->setFeatureOne($item['featureOne'] ?? null)
                ->setFeatureTwo($item['featureTwo'] ?? null)
                ->setFeatureThree($item['featureThree'] ?? null)
                ->setFitDetails($item['fitDetails'] ?? null)
                ->setMaterialDetails($item['materialDetails'] ?? null)
                ->setShippingDetails($item['shippingDetails'] ?? null)
                ->setSizeGuideText($item['sizeGuideText'] ?? null)
                ->setIsMonthlyOffer($item['isMonthlyOffer'])
                ->setPromotionStartsAt($item['promotionStartsAt'] ?? null)
                ->setPromotionEndsAt($item['promotionEndsAt'] ?? null)
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

            $this->synchronizeProductVariants($product, $item['variants'] ?? []);

            if ($product->hasVariants() && null !== $product->getDefaultVariant()) {
                $product
                    ->setPriceCents($product->getDefaultVariant()->getPriceCents())
                    ->setStock($product->getDisplayStock());
            }

            $this->entityManager->persist($product);
        }

        foreach (['tshirt-sound-of-memories', 'sneakers-fan-base'] as $obsoleteSlug) {
            $obsoleteProduct = $this->entityManager->getRepository(Product::class)->findOneBy(['slug' => $obsoleteSlug]);

            if ($obsoleteProduct instanceof Product) {
                $this->entityManager->remove($obsoleteProduct);
            }
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
            ->setSiteName('Sound Of Memories')
            ->setTagline('Fan base officielle non-officielle, merchandising, dates de concert et atmosphère metal moderne.')
            ->setHeaderLogo('/uploads/legacy/Logo.png')
            ->setHomeHeroBackground('/uploads/legacy/htmlwallpaper.jpg')
            ->setHomeHeroVisual('/uploads/legacy/SofM-cover.png')
            ->setHomeHeroTitle('Sound Of Memories reprend la scène.')
            ->setHomeHeroText('Un site fan base mobile-first pour suivre le groupe, retrouver les sorties, acheter le merch et garder l’énergie du live.')
            ->setHomeOverviewImageOne('/uploads/legacy/bannerSand.png')
            ->setHomeOverviewImageTwo('/uploads/legacy/bannerLiving.png')
            ->setHomeOverviewImageThree('/uploads/legacy/bannerToDel.png')
            ->setShopHeroBackground('/uploads/legacy/Sof.jpg')
            ->setSoundcloudUrl('https://soundcloud.com/soundofmemoriesmusic')
            ->setSpotifyUrl('https://open.spotify.com/album/2yemZEPhsqWdvWnKJh7rzd')
            ->setAppleMusicUrl('https://music.apple.com/fr/search?term=Sound%20Of%20Memories')
            ->setYoutubeMusicUrl('https://music.youtube.com/search?q=Sound%20Of%20Memories');

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
                'name' => 'Vêtements',
                'slug' => 'vetements',
                'description' => 'T-shirts, hoodies et pièces à porter au quotidien ou en concert.',
            ],
            [
                'name' => 'Affiches & art',
                'slug' => 'affiches-art',
                'description' => 'Posters, visuels album et pièces murales pour prolonger la direction artistique du groupe.',
            ],
            [
                'name' => 'Accessoires',
                'slug' => 'accessoires',
                'description' => 'Petites pièces fan base à glisser dans un setup ou un cadeau.',
            ],
            [
                'name' => 'Collectors',
                'slug' => 'collectors',
                'description' => 'Objets et éditions qui prolongent l’univers du groupe.',
            ],
            [
                'name' => 'Musique',
                'slug' => 'musique',
                'description' => 'Albums, EP et formats physiques liés aux sorties Sound Of Memories.',
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
                'name' => 'T-shirt Logo Ritual',
                'slug' => 'tshirt-logo-ritual',
                'shortDescription' => 'Le basique live-ready avec le logo Sound Of Memories au premier plan.',
                'catalogExcerpt' => 'Un t-shirt noir pensé comme la porte d’entrée parfaite dans le merch du groupe.',
                'description' => "Pièce essentielle du merch Sound Of Memories. Coupe simple, logo fort, lecture immédiate en salle comme dans la rue. Le produit parfait pour lancer un panier rapidement.",
                'variantChoiceLabel' => 'Taille',
                'priceCents' => 2800,
                'stock' => 38,
                'coverImage' => '/uploads/legacy/Tee-shirtSOM.png',
                'animationKey' => 'vinyl',
                'merchBadge' => 'Best seller',
                'merchBadgeTone' => 'ember',
                'sortPosition' => 10,
                'featureOne' => 'Logo poitrine haute densité',
                'featureTwo' => 'Coupe unisexe scène / quotidien',
                'featureThree' => 'Drop phare de la boutique',
                'fitDetails' => 'Coupe régulière, facile à porter sur scène comme au quotidien.',
                'materialDetails' => 'Coton noir épais avec impression frontale contrastée.',
                'shippingDetails' => 'Expédition suivie, plié à plat pour limiter les marques.',
                'sizeGuideText' => 'Si vous hésitez entre deux tailles, prenez la plus large pour un tombé plus live.',
                'isMonthlyOffer' => true,
                'promotionStartsAt' => new \DateTimeImmutable('2026-04-01 00:00:00'),
                'promotionEndsAt' => new \DateTimeImmutable('2026-04-30 23:59:59'),
                'offerBannerEyebrow' => 'Drop mis en avant',
                'offerBannerTitle' => 'Le classique qui ouvre la boutique',
                'offerBannerText' => 'Un vrai best seller d’entrée de gamme avec tailles gérables, stock par variante et promo immédiate.',
                'offerBannerImage' => '/uploads/legacy/vueTShirt.png',
                'offerBannerPriceBefore' => '32 EUR',
                'offerBannerPriceAfter' => '28 EUR',
                'readingLevel' => 2,
                'difficultyLevel' => 1,
                'maturityLevel' => 2,
                'ambianceLevel' => 4,
                'categorySlug' => 'vetements',
                'variants' => [
                    ['label' => 'S', 'sku' => 'SOM-TS-S', 'priceCents' => 2800, 'compareAtPriceCents' => 3200, 'stock' => 7, 'position' => 1, 'isDefault' => false, 'isPublished' => true],
                    ['label' => 'M', 'sku' => 'SOM-TS-M', 'priceCents' => 2800, 'compareAtPriceCents' => 3200, 'stock' => 11, 'position' => 2, 'isDefault' => true, 'isPublished' => true],
                    ['label' => 'L', 'sku' => 'SOM-TS-L', 'priceCents' => 2800, 'compareAtPriceCents' => 3200, 'stock' => 10, 'position' => 3, 'isDefault' => false, 'isPublished' => true],
                    ['label' => 'XL', 'sku' => 'SOM-TS-XL', 'priceCents' => 2900, 'compareAtPriceCents' => 3300, 'stock' => 10, 'position' => 4, 'isDefault' => false, 'isPublished' => true],
                ],
            ],
            [
                'name' => 'Hoodie Shadow Chapel',
                'slug' => 'hoodie-shadow-chapel',
                'shortDescription' => 'Le hoodie plus massif pour les fans qui veulent porter le groupe hors scène.',
                'catalogExcerpt' => 'Une pièce plus premium, pensée comme le vrai vêtement fort du catalogue.',
                'description' => "Le hoodie Sound Of Memories pousse plus loin le registre merch. Matière plus dense, rendu plus premium, et un jeu de tailles prêt pour un vrai pilotage e-commerce depuis l’admin.",
                'variantChoiceLabel' => 'Taille',
                'priceCents' => 6200,
                'stock' => 20,
                'coverImage' => '/uploads/legacy/vueTShirt.png',
                'animationKey' => 'pulse',
                'merchBadge' => 'Premium',
                'merchBadgeTone' => 'steel',
                'sortPosition' => 20,
                'featureOne' => 'Molleton plus dense',
                'featureTwo' => 'Pièce forte hors scène',
                'featureThree' => 'Stock court par taille',
                'fitDetails' => 'Coupe droite, légèrement ample pour superposition facile.',
                'materialDetails' => 'Molleton intérieur brossé, rendu plus lourd et plus premium.',
                'shippingDetails' => 'Envoi suivi avec emballage renforcé pour garder le volume.',
                'sizeGuideText' => 'Prenez votre taille habituelle pour un rendu net, une taille au-dessus pour un fit plus oversize.',
                'isMonthlyOffer' => false,
                'promotionStartsAt' => new \DateTimeImmutable('2026-04-15 00:00:00'),
                'promotionEndsAt' => new \DateTimeImmutable('2026-05-05 23:59:59'),
                'offerBannerEyebrow' => null,
                'offerBannerTitle' => null,
                'offerBannerText' => null,
                'offerBannerImage' => null,
                'offerBannerPriceBefore' => null,
                'offerBannerPriceAfter' => null,
                'readingLevel' => 4,
                'difficultyLevel' => 2,
                'maturityLevel' => 4,
                'ambianceLevel' => 4,
                'categorySlug' => 'vetements',
                'variants' => [
                    ['label' => 'M', 'sku' => 'SOM-HD-M', 'priceCents' => 6200, 'compareAtPriceCents' => 6900, 'stock' => 7, 'position' => 1, 'isDefault' => true, 'isPublished' => true],
                    ['label' => 'L', 'sku' => 'SOM-HD-L', 'priceCents' => 6200, 'compareAtPriceCents' => 6900, 'stock' => 8, 'position' => 2, 'isDefault' => false, 'isPublished' => true],
                    ['label' => 'XL', 'sku' => 'SOM-HD-XL', 'priceCents' => 6500, 'compareAtPriceCents' => 7200, 'stock' => 5, 'position' => 3, 'isDefault' => false, 'isPublished' => true],
                ],
            ],
            [
                'name' => 'Pin Sigil SOM',
                'slug' => 'pin-sigil-som',
                'shortDescription' => 'Un pin compact à glisser sur une veste, un sac ou un flightcase.',
                'catalogExcerpt' => 'Petit prix, lecture immédiate, parfait pour ouvrir un panier sans friction.',
                'description' => "Le pin logo Sound Of Memories est pensé comme l’accessoire simple et efficace. Un produit facile à pousser en cross-sell, avec une gestion de stock directe et une édition unique.",
                'variantChoiceLabel' => 'Edition',
                'priceCents' => 900,
                'stock' => 50,
                'coverImage' => '/uploads/legacy/Logo.png',
                'animationKey' => 'embers',
                'merchBadge' => 'Petit prix',
                'merchBadgeTone' => 'forest',
                'sortPosition' => 40,
                'featureOne' => 'Accessoire entrée de gamme',
                'featureTwo' => 'Lecture immédiate du logo',
                'featureThree' => 'Parfait en ajout panier',
                'fitDetails' => 'Format unique pour veste, sac, board ou flightcase.',
                'materialDetails' => 'Pin métal compact, pensé comme un petit collector durable.',
                'shippingDetails' => 'Glissé à part dans une protection rigide.',
                'sizeGuideText' => 'Format unique.',
                'isMonthlyOffer' => false,
                'offerBannerEyebrow' => null,
                'offerBannerTitle' => null,
                'offerBannerText' => null,
                'offerBannerImage' => null,
                'offerBannerPriceBefore' => null,
                'offerBannerPriceAfter' => null,
                'readingLevel' => 1,
                'difficultyLevel' => 1,
                'maturityLevel' => 1,
                'ambianceLevel' => 3,
                'categorySlug' => 'accessoires',
                'variants' => [
                    ['label' => 'One size', 'sku' => 'SOM-PIN-OS', 'priceCents' => 900, 'compareAtPriceCents' => null, 'stock' => 50, 'position' => 1, 'isDefault' => true, 'isPublished' => true],
                ],
            ],
            [
                'name' => 'Poster To Deliverance',
                'slug' => 'poster-to-deliverance',
                'shortDescription' => 'Le visuel album en format mural, prêt à encadrer.',
                'catalogExcerpt' => 'Une pièce déco forte pour transformer le coin écoute en sanctuaire metal.',
                'description' => "Ce poster reprend l’imagerie de To Deliverance avec plusieurs formats, pour un produit très visuel qui fonctionne bien en catalogue comme en achat d’impulsion.",
                'variantChoiceLabel' => 'Format',
                'priceCents' => 1800,
                'stock' => 24,
                'coverImage' => '/uploads/legacy/bannerToDel.png',
                'animationKey' => 'glitch',
                'merchBadge' => 'Wall art',
                'merchBadgeTone' => 'sand',
                'sortPosition' => 30,
                'featureOne' => 'Visuel album iconique',
                'featureTwo' => 'Deux formats encadrables',
                'featureThree' => 'Parfait pour coin écoute',
                'fitDetails' => 'Disponible en A3 et A2 pour s’adapter au mur comme au studio.',
                'materialDetails' => 'Impression mate pensée pour un rendu plus magazine / affiche.',
                'shippingDetails' => 'Tube renforcé pour les grands formats.',
                'sizeGuideText' => 'A3 pour une lecture compacte, A2 pour un vrai statement mural.',
                'isMonthlyOffer' => false,
                'offerBannerEyebrow' => null,
                'offerBannerTitle' => null,
                'offerBannerText' => null,
                'offerBannerImage' => null,
                'offerBannerPriceBefore' => null,
                'offerBannerPriceAfter' => null,
                'readingLevel' => 2,
                'difficultyLevel' => 1,
                'maturityLevel' => 2,
                'ambianceLevel' => 5,
                'categorySlug' => 'affiches-art',
                'variants' => [
                    ['label' => 'A3', 'sku' => 'SOM-POSTER-TD-A3', 'priceCents' => 1800, 'compareAtPriceCents' => null, 'stock' => 14, 'position' => 1, 'isDefault' => true, 'isPublished' => true],
                    ['label' => 'A2', 'sku' => 'SOM-POSTER-TD-A2', 'priceCents' => 2600, 'compareAtPriceCents' => 3000, 'stock' => 10, 'position' => 2, 'isDefault' => false, 'isPublished' => true],
                ],
            ],
            [
                'name' => 'Poster Living Circles',
                'slug' => 'poster-living-circles',
                'shortDescription' => 'La première ère Sound Of Memories en grand format.',
                'catalogExcerpt' => 'Une affiche collector qui fonctionne aussi bien pour la déco que pour les fans longue date.',
                'description' => "Poster inspiré par Living Circles, avec un format facile à vendre et à envoyer, idéal pour compléter un panier textile ou musique.",
                'variantChoiceLabel' => 'Format',
                'priceCents' => 1700,
                'stock' => 20,
                'coverImage' => '/uploads/legacy/bannerLiving.png',
                'animationKey' => 'pulse',
                'merchBadge' => 'Collector',
                'merchBadgeTone' => 'steel',
                'sortPosition' => 31,
                'featureOne' => 'Première ère du groupe',
                'featureTwo' => 'Belle pièce de décor',
                'featureThree' => 'Stock volontairement limité',
                'fitDetails' => 'Formats A3 et A2 pour compléter la série visuelle du groupe.',
                'materialDetails' => 'Papier premium avec rendu légèrement texturé.',
                'shippingDetails' => 'Expédition roulée et suivie.',
                'sizeGuideText' => 'A2 si vous voulez un rendu plus expo / collector.',
                'isMonthlyOffer' => false,
                'offerBannerEyebrow' => null,
                'offerBannerTitle' => null,
                'offerBannerText' => null,
                'offerBannerImage' => null,
                'offerBannerPriceBefore' => null,
                'offerBannerPriceAfter' => null,
                'readingLevel' => 2,
                'difficultyLevel' => 1,
                'maturityLevel' => 2,
                'ambianceLevel' => 4,
                'categorySlug' => 'affiches-art',
                'variants' => [
                    ['label' => 'A3', 'sku' => 'SOM-POSTER-LC-A3', 'priceCents' => 1700, 'compareAtPriceCents' => null, 'stock' => 12, 'position' => 1, 'isDefault' => true, 'isPublished' => true],
                    ['label' => 'A2', 'sku' => 'SOM-POSTER-LC-A2', 'priceCents' => 2500, 'compareAtPriceCents' => 2900, 'stock' => 8, 'position' => 2, 'isDefault' => false, 'isPublished' => true],
                ],
            ],
            [
                'name' => 'To Deliverance CD',
                'slug' => 'to-deliverance-cd',
                'shortDescription' => 'L’album en format physique pour celles et ceux qui veulent l’objet autant que l’écoute.',
                'catalogExcerpt' => 'Format simple à commander, simple à expédier, parfait pour une boutique merch complète.',
                'description' => "Le support physique reste essentiel dans une boutique de groupe. Ce produit s’intègre naturellement au panier, seul ou couplé à un textile, avec une logique de stock très simple.",
                'variantChoiceLabel' => 'Format',
                'priceCents' => 1400,
                'stock' => 30,
                'coverImage' => '/uploads/legacy/Deliv.jpg',
                'animationKey' => 'vinyl',
                'merchBadge' => 'Album phare',
                'merchBadgeTone' => 'ember',
                'sortPosition' => 50,
                'featureOne' => 'Support physique essentiel',
                'featureTwo' => 'Facile à ajouter au panier',
                'featureThree' => 'Existe en bundle signé',
                'fitDetails' => 'Digipack classique ou bundle signé selon le niveau d’engagement fan.',
                'materialDetails' => 'CD physique avec visuel album, parfait pour compléter un achat textile.',
                'shippingDetails' => 'Expédié en protection rigide adaptée aux formats musicaux.',
                'sizeGuideText' => 'Le bundle signé est la meilleure option pour un cadeau ou un collector fan base.',
                'isMonthlyOffer' => false,
                'offerBannerEyebrow' => null,
                'offerBannerTitle' => null,
                'offerBannerText' => null,
                'offerBannerImage' => null,
                'offerBannerPriceBefore' => null,
                'offerBannerPriceAfter' => null,
                'readingLevel' => 1,
                'difficultyLevel' => 1,
                'maturityLevel' => 2,
                'ambianceLevel' => 4,
                'categorySlug' => 'musique',
                'variants' => [
                    ['label' => 'Digipack', 'sku' => 'SOM-TD-CD', 'priceCents' => 1400, 'compareAtPriceCents' => null, 'stock' => 18, 'position' => 1, 'isDefault' => true, 'isPublished' => true],
                    ['label' => 'Bundle signé', 'sku' => 'SOM-TD-SIGNED', 'priceCents' => 2200, 'compareAtPriceCents' => 2500, 'stock' => 12, 'position' => 2, 'isDefault' => false, 'isPublished' => true],
                ],
            ],
            [
                'name' => 'Living Circles EP',
                'slug' => 'living-circles-ep',
                'shortDescription' => 'Le premier souffle studio du groupe, en édition physique.',
                'catalogExcerpt' => 'Une pièce musique plus collector pour raconter l’histoire du groupe dans la boutique.',
                'description' => "Living Circles garde une vraie valeur narrative dans le catalogue. C’est la pièce idéale pour nourrir la rubrique discographie et donner plus de profondeur au panier.",
                'variantChoiceLabel' => 'Format',
                'priceCents' => 1200,
                'stock' => 18,
                'coverImage' => '/uploads/legacy/Living.jpg',
                'animationKey' => 'vinyl',
                'merchBadge' => 'Origines',
                'merchBadgeTone' => 'forest',
                'sortPosition' => 51,
                'featureOne' => 'Premier souffle studio',
                'featureTwo' => 'Fort capital narratif',
                'featureThree' => 'Excellent cross-sell discographie',
                'fitDetails' => 'Disponible en CD simple ou en bundle artwork plus collectionneur.',
                'materialDetails' => 'Format physique conçu pour donner de la profondeur à la discographie merch.',
                'shippingDetails' => 'Expédié à plat avec protection rigide.',
                'sizeGuideText' => 'Le bundle artwork est la version la plus collector.',
                'isMonthlyOffer' => false,
                'offerBannerEyebrow' => null,
                'offerBannerTitle' => null,
                'offerBannerText' => null,
                'offerBannerImage' => null,
                'offerBannerPriceBefore' => null,
                'offerBannerPriceAfter' => null,
                'readingLevel' => 1,
                'difficultyLevel' => 1,
                'maturityLevel' => 2,
                'ambianceLevel' => 4,
                'categorySlug' => 'musique',
                'variants' => [
                    ['label' => 'CD', 'sku' => 'SOM-LC-CD', 'priceCents' => 1200, 'compareAtPriceCents' => null, 'stock' => 10, 'position' => 1, 'isDefault' => true, 'isPublished' => true],
                    ['label' => 'Bundle artwork', 'sku' => 'SOM-LC-BUNDLE', 'priceCents' => 1900, 'compareAtPriceCents' => 2200, 'stock' => 8, 'position' => 2, 'isDefault' => false, 'isPublished' => true],
                ],
            ],
            [
                'name' => 'Montre Collector SOM',
                'slug' => 'montre-collector-som',
                'shortDescription' => 'Une pièce premium pour les fans qui veulent un collector plus inattendu.',
                'catalogExcerpt' => 'Objet collector haut de gamme, parfait pour une offre plus ambitieuse dans le catalogue.',
                'description' => "Cette montre collector s’adresse aux fans qui veulent un objet moins attendu que le textile ou le poster. Une pièce premium avec une logique de stock courte, simple à suivre dans l’admin.",
                'variantChoiceLabel' => 'Edition',
                'priceCents' => 14000,
                'stock' => 8,
                'coverImage' => '/uploads/legacy/SOFWatch.jpg',
                'animationKey' => 'glitch',
                'merchBadge' => 'Ultra limité',
                'merchBadgeTone' => 'steel',
                'sortPosition' => 80,
                'featureOne' => 'Collector premium',
                'featureTwo' => 'Stock volontairement court',
                'featureThree' => 'Pièce inattendue du catalogue',
                'fitDetails' => 'Edition collector unique pour les fans qui veulent une pièce plus rare.',
                'materialDetails' => 'Accessoire premium pensé comme objet de collection autant que produit merch.',
                'shippingDetails' => 'Emballage renforcé et suivi complet.',
                'sizeGuideText' => 'Edition unique, disponible tant que le stock court tient.',
                'isMonthlyOffer' => false,
                'offerBannerEyebrow' => null,
                'offerBannerTitle' => null,
                'offerBannerText' => null,
                'offerBannerImage' => null,
                'offerBannerPriceBefore' => null,
                'offerBannerPriceAfter' => null,
                'readingLevel' => 5,
                'difficultyLevel' => 2,
                'maturityLevel' => 5,
                'ambianceLevel' => 4,
                'categorySlug' => 'collectors',
                'variants' => [
                    ['label' => 'Collector', 'sku' => 'SOM-WATCH-COLLECTOR', 'priceCents' => 14000, 'compareAtPriceCents' => null, 'stock' => 8, 'position' => 1, 'isDefault' => true, 'isPublished' => true],
                ],
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

    /**
     * @param array<int, array<string, mixed>> $variants
     */
    private function synchronizeProductVariants(Product $product, array $variants): void
    {
        $existingVariants = [];
        foreach ($product->getVariants() as $variant) {
            $existingVariants[$variant->getSku() ?: $variant->getLabel()] = $variant;
        }

        $incomingKeys = [];

        foreach ($variants as $variantData) {
            $key = (string) ($variantData['sku'] ?? $variantData['label'] ?? '');
            $incomingKeys[] = $key;

            $variant = $existingVariants[$key] ?? new ProductVariant();
            $variant
                ->setLabel((string) $variantData['label'])
                ->setSku($variantData['sku'] ?? null)
                ->setPriceCents((int) $variantData['priceCents'])
                ->setCompareAtPriceCents(isset($variantData['compareAtPriceCents']) ? (int) $variantData['compareAtPriceCents'] : null)
                ->setStock((int) $variantData['stock'])
                ->setPosition((int) ($variantData['position'] ?? 0))
                ->setIsDefault((bool) ($variantData['isDefault'] ?? false))
                ->setIsPublished((bool) ($variantData['isPublished'] ?? true))
                ->setProduct($product);

            if (!$product->getVariants()->contains($variant)) {
                $product->addVariant($variant);
            }
        }

        foreach ($product->getVariants()->toArray() as $variant) {
            $key = $variant->getSku() ?: $variant->getLabel();

            if (!in_array($key, $incomingKeys, true)) {
                $product->removeVariant($variant);
            }
        }
    }
}
