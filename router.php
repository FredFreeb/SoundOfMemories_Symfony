<?php

declare(strict_types=1);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

if (is_string($path) && '' !== $path) {
    $publicPath = __DIR__ . '/public' . $path;

    if (is_file($publicPath)) {
        return false;
    }
}

require __DIR__ . '/public/index.php';
