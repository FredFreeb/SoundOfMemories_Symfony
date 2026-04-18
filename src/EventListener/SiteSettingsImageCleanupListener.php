<?php

namespace App\EventListener;

use App\Entity\SiteSettings;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;

#[AsDoctrineListener(event: Events::postUpdate)]
class SiteSettingsImageCleanupListener
{
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof SiteSettings) {
            return;
        }
        // Les visuels du site sont maintenant choisis dans une bibliothèque partagée.
        // Un changement de sélection ne doit donc jamais supprimer le fichier source.
    }
}
