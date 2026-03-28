<?php

namespace App\EventListener;

use App\Entity\SiteSettings;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postUpdate)]
class SiteSettingsImageCleanupListener
{
    private ?array $pendingRemoval = null;

    public function __construct(
        #[Autowire(param: 'site_images_dir')]
        private readonly string $siteImagesDir,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof SiteSettings) {
            return;
        }

        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        foreach (['headerLogo', 'homeHeroBackground', 'homeHeroVisual', 'homeOverviewImageOne', 'homeOverviewImageTwo', 'homeOverviewImageThree', 'homeSpecialOfferImage', 'shopHeroBackground'] as $field) {
            if (!isset($changeSet[$field])) {
                continue;
            }

            [$oldValue] = $changeSet[$field];
            $this->deleteIfLocal($oldValue);
        }
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof SiteSettings) {
            return;
        }

        $this->pendingRemoval = [
            $entity->getHeaderLogo(),
            $entity->getHomeHeroBackground(),
            $entity->getHomeHeroVisual(),
            $entity->getHomeOverviewImageOne(),
            $entity->getHomeOverviewImageTwo(),
            $entity->getHomeOverviewImageThree(),
            $entity->getHomeSpecialOfferImage(),
            $entity->getShopHeroBackground(),
        ];
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof SiteSettings || null === $this->pendingRemoval) {
            return;
        }

        foreach ($this->pendingRemoval as $path) {
            $this->deleteIfLocal($path);
        }

        $this->pendingRemoval = null;
    }

    private function deleteIfLocal(?string $path): void
    {
        if (null === $path || '' === trim($path) || str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return;
        }

        $fullPath = rtrim($this->siteImagesDir, '/') . '/' . ltrim($path, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
