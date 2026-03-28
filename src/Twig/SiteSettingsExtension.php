<?php

namespace App\Twig;

use App\Entity\SiteSettings;
use App\Service\SiteSettingsProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SiteSettingsExtension extends AbstractExtension
{
    public function __construct(
        private readonly SiteSettingsProvider $settingsProvider,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_settings', fn (): SiteSettings => $this->settingsProvider->getCurrent()),
            new TwigFunction('site_asset', fn (?string $path, string $folder = 'site'): ?string => $this->settingsProvider->resolvePublicPath($path, $folder)),
        ];
    }
}
