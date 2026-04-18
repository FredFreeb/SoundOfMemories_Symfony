<?php

namespace App\EventSubscriber;

use App\Entity\Concert;
use App\Entity\EditorialModule;
use App\Entity\EditorialModuleItem;
use App\Entity\GalleryPhoto;
use App\Entity\PressMention;
use App\Entity\Product;
use App\Entity\ProductGalleryImage;
use App\Service\UploadedImageWebpConverter;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EasyAdminWebpConversionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UploadedImageWebpConverter $uploadedImageWebpConverter,
        private readonly string $projectDir,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => 'convertUploadedImagesToWebp',
            AfterEntityUpdatedEvent::class => 'convertUploadedImagesToWebp',
        ];
    }

    public function convertUploadedImagesToWebp(object $event): void
    {
        $entity = $event->getEntityInstance();

        if (!$this->convertEntityImages($entity)) {
            return;
        }

        $this->entityManager->flush();
    }

    private function convertEntityImages(object $entity): bool
    {
        $hasChanges = false;

        if ($entity instanceof Product) {
            $hasChanges = $this->convertRelativeUpload($entity, 'getCoverImage', 'setCoverImage', 'public/uploads/products') || $hasChanges;
            $hasChanges = $this->convertRelativeUpload($entity, 'getOfferBannerImage', 'setOfferBannerImage', 'public/uploads/products') || $hasChanges;

            foreach ($entity->getGalleryImages() as $galleryImage) {
                $hasChanges = $this->convertEntityImages($galleryImage) || $hasChanges;
            }

            return $hasChanges;
        }

        if ($entity instanceof ProductGalleryImage) {
            return $this->convertRelativeUpload($entity, 'getImagePath', 'setImagePath', 'public/uploads/product-gallery');
        }

        if ($entity instanceof GalleryPhoto) {
            return $this->convertRelativeUpload($entity, 'getImagePath', 'setImagePath', 'public/uploads/gallery');
        }

        if ($entity instanceof PressMention) {
            return $this->convertRelativeUpload($entity, 'getPhoto', 'setPhoto', 'public/uploads/press');
        }

        if ($entity instanceof Concert) {
            return $this->convertRelativeUpload($entity, 'getPosterImage', 'setPosterImage', 'public/uploads/concerts');
        }

        if ($entity instanceof EditorialModule) {
            $hasChanges = $this->convertRelativeUpload($entity, 'getImagePath', 'setImagePath', 'public/uploads/editorial') || $hasChanges;
            $hasChanges = $this->convertRelativeUpload($entity, 'getBackgroundImagePath', 'setBackgroundImagePath', 'public/uploads/editorial') || $hasChanges;

            foreach ($entity->getItems() as $item) {
                $hasChanges = $this->convertEntityImages($item) || $hasChanges;
            }

            return $hasChanges;
        }

        if ($entity instanceof EditorialModuleItem) {
            return $this->convertRelativeUpload($entity, 'getImagePath', 'setImagePath', 'public/uploads/editorial');
        }

        return false;
    }

    private function convertRelativeUpload(object $entity, string $getter, string $setter, string $relativeDirectory): bool
    {
        $currentPath = $entity->{$getter}();
        if (!is_string($currentPath) || '' === trim($currentPath)) {
            return false;
        }

        if (str_starts_with($currentPath, 'http://') || str_starts_with($currentPath, 'https://') || str_starts_with($currentPath, '/')) {
            return false;
        }

        if ('webp' === strtolower((string) pathinfo($currentPath, \PATHINFO_EXTENSION))) {
            return false;
        }

        $absolutePath = rtrim($this->projectDir, '/') . '/' . trim($relativeDirectory, '/') . '/' . ltrim($currentPath, '/');
        $convertedPath = $this->uploadedImageWebpConverter->convertToWebp($absolutePath);
        if ($convertedPath === $absolutePath) {
            return false;
        }

        $entity->{$setter}(basename($convertedPath));

        return true;
    }
}
