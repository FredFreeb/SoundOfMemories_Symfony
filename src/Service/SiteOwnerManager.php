<?php

namespace App\Service;

final class SiteOwnerManager
{
    private const STORAGE_FILE = 'var/secure/site-owner.json';

    public function __construct(
        private readonly string $projectDir,
        private readonly string $siteOwnerDefaultName,
        private readonly string $siteOwnerDefaultEmail,
        private readonly string $siteOwnerDefaultAddress,
    ) {
    }

    public function getOwner(): array
    {
        $defaults = [
            'name' => $this->siteOwnerDefaultName,
            'email' => $this->siteOwnerDefaultEmail,
            'address' => $this->siteOwnerDefaultAddress,
        ];

        $path = $this->getStoragePath();

        if (!is_file($path)) {
            return $defaults;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (!\is_array($data)) {
            return $defaults;
        }

        return [
            'name' => (string) ($data['name'] ?? $defaults['name']),
            'email' => (string) ($data['email'] ?? $defaults['email']),
            'address' => (string) ($data['address'] ?? $defaults['address']),
        ];
    }

    public function getOwnerName(): string
    {
        return $this->getOwner()['name'];
    }

    public function getOwnerEmail(): string
    {
        return $this->getOwner()['email'];
    }

    public function getOwnerAddress(): string
    {
        return $this->getOwner()['address'];
    }

    public function saveOwner(string $name, string $email, ?string $address = null): void
    {
        $directory = dirname($this->getStoragePath());

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($this->getStoragePath(), json_encode([
            'name' => trim($name) !== '' ? trim($name) : $this->siteOwnerDefaultName,
            'email' => mb_strtolower(trim($email)),
            'address' => null !== $address && trim($address) !== '' ? trim($address) : $this->siteOwnerDefaultAddress,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function getStoragePath(): string
    {
        return rtrim($this->projectDir, '/').'/'.self::STORAGE_FILE;
    }
}
