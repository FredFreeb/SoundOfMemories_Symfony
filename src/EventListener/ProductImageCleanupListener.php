<?php

namespace App\EventListener;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postRemove)]
class ProductImageCleanupListener
{
    /**
     * @var array<int, array<int, string>>
     */
    private array $imagesToDeleteAfterUpdate = [];

    /**
     * @var array<int, array<int, string>>
     */
    private array $imagesToDeleteAfterRemove = [];

    public function __construct(
        #[Autowire(param: 'product_images_dir')]
        private readonly string $productImagesDir,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $product = $event->getObject();

        if (!$product instanceof Product) {
            return;
        }

        $images = [];
        foreach (['coverImage', 'offerBannerImage'] as $fieldName) {
            if (!$event->hasChangedField($fieldName)) {
                continue;
            }

            $oldImage = $event->getOldValue($fieldName);
            if (\is_string($oldImage) && $this->isManagedLocalImage($oldImage)) {
                $images[] = $oldImage;
            }
        }

        if ([] === $images) {
            return;
        }

        // Fred note: Je memorise ici l'ancien fichier pour ne le supprimer qu'une fois la mise a jour bien terminee.
        $this->imagesToDeleteAfterUpdate[spl_object_id($product)] = array_values(array_unique($images));
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $product = $event->getObject();

        if (!$product instanceof Product) {
            return;
        }

        $key = spl_object_id($product);
        if (!isset($this->imagesToDeleteAfterUpdate[$key])) {
            return;
        }

        foreach ($this->imagesToDeleteAfterUpdate[$key] as $image) {
            $this->deleteImageFile($image);
        }
        unset($this->imagesToDeleteAfterUpdate[$key]);
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $product = $event->getObject();

        if (!$product instanceof Product) {
            return;
        }

        $images = [];
        foreach ([$product->getCoverImage(), $product->getOfferBannerImage()] as $currentImage) {
            if (\is_string($currentImage) && $this->isManagedLocalImage($currentImage)) {
                $images[] = $currentImage;
            }
        }

        if ([] === $images) {
            return;
        }

        // Fred note: Je garde le nom du fichier avant suppression de l'entite pour nettoyer le disque juste apres.
        $this->imagesToDeleteAfterRemove[spl_object_id($product)] = array_values(array_unique($images));
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $product = $event->getObject();

        if (!$product instanceof Product) {
            return;
        }

        $key = spl_object_id($product);
        if (!isset($this->imagesToDeleteAfterRemove[$key])) {
            return;
        }

        foreach ($this->imagesToDeleteAfterRemove[$key] as $image) {
            $this->deleteImageFile($image);
        }
        unset($this->imagesToDeleteAfterRemove[$key]);
    }

    private function isManagedLocalImage(string $path): bool
    {
        if ('' === trim($path)) {
            return false;
        }

        // Fred note: Je ne touche pas aux URLs distantes ou aux chemins absolus, seulement aux uploads locaux du projet.
        return !str_starts_with($path, 'http://')
            && !str_starts_with($path, 'https://')
            && !str_starts_with($path, '/');
    }

    private function deleteImageFile(string $relativePath): void
    {
        $fullPath = rtrim($this->productImagesDir, '/') . '/' . ltrim($relativePath, '/');
        if (!$this->filesystem->exists($fullPath)) {
            return;
        }

        $this->filesystem->remove($fullPath);
    }
}
