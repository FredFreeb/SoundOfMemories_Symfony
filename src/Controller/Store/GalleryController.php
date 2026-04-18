<?php

namespace App\Controller\Store;

use App\Entity\EditorialModule;
use App\Entity\GalleryPhoto;
use App\Repository\EditorialModuleRepository;
use App\Repository\GalleryPhotoRepository;
use App\Service\SiteSettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GalleryController extends AbstractController
{
    #[Route('/gallery', name: 'store_gallery', methods: ['GET'])]
    public function __invoke(
        SiteSettingsProvider $siteSettings,
        GalleryPhotoRepository $galleryPhotoRepository,
        EditorialModuleRepository $editorialModules,
    ): Response
    {
        $photos = $galleryPhotoRepository->findPublishedOrdered();

        return $this->render('store/gallery/index.html.twig', [
            'siteSettings' => $siteSettings->getCurrent(),
            'editorial' => $editorialModules->findPublishedMapForPage(EditorialModule::PAGE_GALLERY),
            'photos' => [] !== $photos ? array_map(
                static fn (GalleryPhoto $photo): array => [
                    'source' => 'admin',
                    'imagePath' => $photo->getImagePath(),
                    'title' => $photo->getTitle(),
                    'caption' => $photo->getCaption(),
                    'altText' => $photo->getAltText() ?: ($photo->getTitle() ?: 'Photo Sound Of Memories'),
                ],
                $photos
            ) : $this->getFallbackPhotos(),
        ]);
    }

    /**
     * @return list<array{source: string, imagePath: string, title: string, caption: string, altText: string}>
     */
    private function getFallbackPhotos(): array
    {
        return [
            [
                'source' => 'legacy',
                'imagePath' => 'uploads/legacy/gal1.JPG',
                'title' => 'Live en fusion',
                'caption' => 'Archives scène, lumières brûlantes et énergie frontale du groupe.',
                'altText' => 'Sound Of Memories en live sous des lumières rouges',
            ],
            [
                'source' => 'legacy',
                'imagePath' => 'uploads/legacy/gal4.JPG',
                'title' => 'Backstage',
                'caption' => 'Moments plus bruts, presque documentaires, entre deux passages sur scène.',
                'altText' => 'Portrait backstage de Sound Of Memories',
            ],
            [
                'source' => 'legacy',
                'imagePath' => 'uploads/legacy/gal7.JPG',
                'title' => 'Machine organique',
                'caption' => 'Une matière visuelle plus froide, plus industrielle, en plein cœur de l’univers SOM.',
                'altText' => 'Photo promo sombre de Sound Of Memories',
            ],
            [
                'source' => 'legacy',
                'imagePath' => 'uploads/legacy/gal10.JPG',
                'title' => 'Sur scène',
                'caption' => 'Une archive live plus frontale pensée comme une affiche de tournée.',
                'altText' => 'Sound Of Memories sur scène devant le public',
            ],
            [
                'source' => 'legacy',
                'imagePath' => 'uploads/legacy/gal13.jpg',
                'title' => 'Portrait',
                'caption' => 'Le versant plus cinématographique du projet, entre portrait et mémoire visuelle.',
                'altText' => 'Portrait de Sound Of Memories',
            ],
            [
                'source' => 'legacy',
                'imagePath' => 'uploads/legacy/gal15.jpg',
                'title' => 'Archives',
                'caption' => 'Textures live et souvenirs de scène pour nourrir la galerie avant l’admin.',
                'altText' => 'Archive photo Sound Of Memories',
            ],
            [
                'source' => 'legacy',
                'imagePath' => 'uploads/legacy/gal16.JPG',
                'title' => 'Concert',
                'caption' => 'Un rendu plus dense, parfait pour les sections de galerie plus immersives.',
                'altText' => 'Concert Sound Of Memories avec éclairage intense',
            ],
            [
                'source' => 'legacy',
                'imagePath' => 'uploads/legacy/gal18.JPG',
                'title' => 'Mémoire scénique',
                'caption' => 'Un dernier visuel d’archive pour garder une galerie vivante avant les prochains ajouts.',
                'altText' => 'Photo d archive de Sound Of Memories',
            ],
        ];
    }
}
