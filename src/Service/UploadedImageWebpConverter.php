<?php

namespace App\Service;

final class UploadedImageWebpConverter
{
    public function convertToWebp(string $absolutePath, int $quality = 84): string
    {
        if (!is_file($absolutePath)) {
            return $absolutePath;
        }

        $extension = strtolower((string) pathinfo($absolutePath, \PATHINFO_EXTENSION));
        if ('webp' === $extension) {
            return $absolutePath;
        }

        if (!function_exists('imagewebp')) {
            throw new \RuntimeException('La conversion WebP nécessite l’extension GD avec le support WebP activé.');
        }

        $image = $this->createImageResource($absolutePath);
        if (!$image instanceof \GdImage) {
            return $absolutePath;
        }

        if (function_exists('imageistruecolor') && !imageistruecolor($image) && function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $targetPath = $this->buildTargetPath($absolutePath);
        if (!imagewebp($image, $targetPath, $quality)) {
            imagedestroy($image);

            throw new \RuntimeException(sprintf('Impossible de convertir le fichier "%s" en WebP.', $absolutePath));
        }

        imagedestroy($image);

        if ($targetPath !== $absolutePath && is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        return $targetPath;
    }

    private function buildTargetPath(string $absolutePath): string
    {
        $directory = (string) pathinfo($absolutePath, \PATHINFO_DIRNAME);
        $filename = (string) pathinfo($absolutePath, \PATHINFO_FILENAME);

        return rtrim($directory, '/') . '/' . $filename . '.webp';
    }

    private function createImageResource(string $absolutePath): \GdImage|null
    {
        $imageInfo = @getimagesize($absolutePath);
        if (false === $imageInfo) {
            return null;
        }

        return match ($imageInfo['mime'] ?? null) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath) ?: null,
            'image/png' => @imagecreatefrompng($absolutePath) ?: null,
            'image/gif' => function_exists('imagecreatefromgif') ? (@imagecreatefromgif($absolutePath) ?: null) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($absolutePath) ?: null) : null,
            default => null,
        };
    }
}
