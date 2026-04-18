<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EditorialExtension extends AbstractExtension
{
    public function __construct(
        private readonly Packages $packages,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('editorial_asset', [$this, 'resolveAsset']),
        ];
    }

    public function resolveAsset(?string $path): ?string
    {
        if (null === $path || '' === trim($path)) {
            return null;
        }

        $normalized = ltrim(trim($path), '/');

        if (str_starts_with($normalized, 'uploads/') || str_starts_with($normalized, 'images/')) {
            return $this->packages->getUrl($normalized);
        }

        return $this->packages->getUrl('uploads/editorial/' . $normalized);
    }
}
