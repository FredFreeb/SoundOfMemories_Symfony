<?php

namespace App\Service;

use App\Entity\SiteSettings;
use App\Repository\SiteSettingsRepository;

class SiteSettingsProvider
{
    public function __construct(
        private readonly SiteSettingsRepository $repository,
    ) {
    }

    public function getCurrent(): SiteSettings
    {
        // Fred note: Je prefere renvoyer un objet par defaut plutot qu'une erreur si la table n'est pas encore remplie.
        return $this->repository->findCurrent() ?? new SiteSettings();
    }

    public function resolvePublicPath(?string $path, string $folder): ?string
    {
        if (null === $path || '' === trim($path)) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        return sprintf('/uploads/%s/%s', trim($folder, '/'), ltrim($path, '/'));
    }
}
