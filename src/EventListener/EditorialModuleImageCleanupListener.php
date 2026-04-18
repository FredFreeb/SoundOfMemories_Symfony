<?php

namespace App\EventListener;

use App\Entity\EditorialModule;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postUpdate)]
final class EditorialModuleImageCleanupListener
{
    /**
     * @var string[]
     */
    private array $pendingRemoval = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/editorial')]
        private readonly string $editorialImagesDir,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof EditorialModule) {
            return;
        }

        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        foreach (['imagePath', 'backgroundImagePath'] as $fieldName) {
            if (!isset($changeSet[$fieldName])) {
                continue;
            }

            [$oldValue] = $changeSet[$fieldName];
            $this->deleteIfLocal($oldValue);
        }
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof EditorialModule) {
            return;
        }

        $this->pendingRemoval = array_values(array_filter([
            $entity->getImagePath(),
            $entity->getBackgroundImagePath(),
        ]));
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof EditorialModule || [] === $this->pendingRemoval) {
            return;
        }

        foreach ($this->pendingRemoval as $path) {
            $this->deleteIfLocal($path);
        }

        $this->pendingRemoval = [];
    }

    private function deleteIfLocal(?string $path): void
    {
        if (null === $path || '' === trim($path) || str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return;
        }

        $fullPath = rtrim($this->editorialImagesDir, '/') . '/' . ltrim($path, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
