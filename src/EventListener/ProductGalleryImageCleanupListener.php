<?php

namespace App\EventListener;

use App\Entity\ProductGalleryImage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postUpdate)]
class ProductGalleryImageCleanupListener
{
    private ?string $pendingRemoval = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/product-gallery')]
        private readonly string $galleryImagesDir,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ProductGalleryImage) {
            return;
        }

        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        if (!isset($changeSet['imagePath'])) {
            return;
        }

        [$oldValue] = $changeSet['imagePath'];
        $this->deleteIfLocal($oldValue);
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ProductGalleryImage) {
            return;
        }

        $this->pendingRemoval = $entity->getImagePath();
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ProductGalleryImage || null === $this->pendingRemoval) {
            return;
        }

        $this->deleteIfLocal($this->pendingRemoval);
        $this->pendingRemoval = null;
    }

    private function deleteIfLocal(?string $path): void
    {
        if (null === $path || '' === trim($path) || str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return;
        }

        $fullPath = rtrim($this->galleryImagesDir, '/') . '/' . ltrim($path, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
