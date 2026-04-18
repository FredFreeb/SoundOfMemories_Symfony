<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViteExtension extends AbstractExtension
{
    private ?array $manifest = null;

    public function __construct(
        private readonly string $projectDir,
        private readonly Packages $packages,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_link_tags', [$this, 'renderLinkTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entry_script_tags', [$this, 'renderScriptTags'], ['is_safe' => ['html']]),
        ];
    }

    public function renderLinkTags(string $entryName): string
    {
        $manifestEntry = $this->getManifestEntry($entryName);

        if (null === $manifestEntry) {
            return '';
        }

        $tags = [];

        foreach ($this->collectCssFiles($entryName) as $cssFile) {
            $href = htmlspecialchars($this->packages->getUrl('build/' . $cssFile), ENT_QUOTES, 'UTF-8');
            $tags[] = sprintf('<link rel="stylesheet" href="%s">', $href);
        }

        return implode("\n", array_unique($tags));
    }

    public function renderScriptTags(string $entryName): string
    {
        $manifestEntry = $this->getManifestEntry($entryName);

        if (null === $manifestEntry || !isset($manifestEntry['file'])) {
            return '';
        }

        $tags = [];

        foreach ($this->collectImportedFiles($entryName) as $importedFile) {
            $href = htmlspecialchars($this->packages->getUrl('build/' . $importedFile), ENT_QUOTES, 'UTF-8');
            $tags[] = sprintf('<link rel="modulepreload" href="%s">', $href);
        }

        $src = htmlspecialchars($this->packages->getUrl('build/' . $manifestEntry['file']), ENT_QUOTES, 'UTF-8');
        $tags[] = sprintf('<script type="module" src="%s"></script>', $src);

        return implode("\n", array_unique($tags));
    }

    private function getManifestEntry(string $entryName): ?array
    {
        $manifest = $this->loadManifest();

        if (!is_array($manifest) || !isset($manifest[$entryName]) || !is_array($manifest[$entryName])) {
            return null;
        }

        return $manifest[$entryName];
    }

    private function loadManifest(): ?array
    {
        if (null !== $this->manifest) {
            return $this->manifest;
        }

        $manifestPath = $this->projectDir . '/public/build/.vite/manifest.json';

        if (!is_file($manifestPath)) {
            $this->manifest = [];

            return $this->manifest;
        }

        $content = file_get_contents($manifestPath);

        if (false === $content) {
            $this->manifest = [];

            return $this->manifest;
        }

        $decoded = json_decode($content, true);
        $this->manifest = is_array($decoded) ? $decoded : [];

        return $this->manifest;
    }

    private function collectCssFiles(string $entryName, array &$seenEntries = []): array
    {
        if (isset($seenEntries[$entryName])) {
            return [];
        }

        $seenEntries[$entryName] = true;
        $entry = $this->getManifestEntry($entryName);

        if (null === $entry) {
            return [];
        }

        $cssFiles = [];

        if (isset($entry['css']) && is_array($entry['css'])) {
            foreach ($entry['css'] as $cssFile) {
                if (is_string($cssFile)) {
                    $cssFiles[] = $cssFile;
                }
            }
        }

        if (isset($entry['imports']) && is_array($entry['imports'])) {
            foreach ($entry['imports'] as $importedEntry) {
                if (is_string($importedEntry)) {
                    $cssFiles = array_merge($cssFiles, $this->collectCssFiles($importedEntry, $seenEntries));
                }
            }
        }

        return array_values(array_unique($cssFiles));
    }

    private function collectImportedFiles(string $entryName, array &$seenEntries = []): array
    {
        if (isset($seenEntries[$entryName])) {
            return [];
        }

        $seenEntries[$entryName] = true;
        $entry = $this->getManifestEntry($entryName);

        if (null === $entry || !isset($entry['imports']) || !is_array($entry['imports'])) {
            return [];
        }

        $files = [];

        foreach ($entry['imports'] as $importedEntry) {
            if (!is_string($importedEntry)) {
                continue;
            }

            $importedManifestEntry = $this->getManifestEntry($importedEntry);

            if (is_array($importedManifestEntry) && isset($importedManifestEntry['file']) && is_string($importedManifestEntry['file'])) {
                $files[] = $importedManifestEntry['file'];
            }

            $files = array_merge($files, $this->collectImportedFiles($importedEntry, $seenEntries));
        }

        return array_values(array_unique($files));
    }
}
