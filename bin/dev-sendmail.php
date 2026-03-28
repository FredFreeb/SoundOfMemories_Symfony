#!/usr/bin/env php
<?php

declare(strict_types=1);

$projectDir = dirname(__DIR__);
$mailboxDir = $projectDir.'/var/dev-mailbox';

if (!is_dir($mailboxDir) && !mkdir($mailboxDir, 0775, true) && !is_dir($mailboxDir)) {
    fwrite(STDERR, "Impossible de créer le dossier de boîte mail locale.\n");

    exit(1);
}

$rawMessage = stream_get_contents(STDIN);

if (!is_string($rawMessage) || trim($rawMessage) === '') {
    fwrite(STDERR, "Aucun contenu email n'a été reçu.\n");

    exit(1);
}

[$headersBlock, $body] = preg_split("/\R\R/", $rawMessage, 2) + ['', ''];
$headers = parseHeaders($headersBlock);
$decodedBody = decodeBody($body, $headers);
$urls = extractUrls($decodedBody);
$createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$id = $createdAt->format('YmdHis').'-'.bin2hex(random_bytes(4));

$metadata = [
    'id' => $id,
    'created_at' => $createdAt->format(DATE_ATOM),
    'subject' => decodeHeaderValue($headers['subject'] ?? 'Sans objet'),
    'from' => decodeHeaderValue($headers['from'] ?? ''),
    'to' => decodeHeaderValue($headers['to'] ?? ''),
    'reply_to' => decodeHeaderValue($headers['reply-to'] ?? ''),
    'date_header' => decodeHeaderValue($headers['date'] ?? ''),
    'content_type' => strtolower($headers['content-type'] ?? ''),
    'preview_text' => createPreviewText($decodedBody),
    'decoded_body' => $decodedBody,
    'urls' => $urls,
    'primary_url' => $urls[0] ?? null,
];

file_put_contents($mailboxDir.'/'.$id.'.eml', $rawMessage);
file_put_contents(
    $mailboxDir.'/'.$id.'.json',
    json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

exit(0);

function parseHeaders(string $headersBlock): array
{
    $headers = [];
    $current = null;

    foreach (preg_split("/\R/", $headersBlock) as $line) {
        if ($line === '') {
            continue;
        }

        if (preg_match('/^\s+/', $line) === 1 && is_string($current) && isset($headers[$current])) {
            $headers[$current] .= ' '.trim($line);

            continue;
        }

        if (!str_contains($line, ':')) {
            continue;
        }

        [$name, $value] = explode(':', $line, 2);
        $current = strtolower(trim($name));
        $headers[$current] = trim($value);
    }

    return $headers;
}

function decodeHeaderValue(string $value): string
{
    $decoded = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

    return trim($decoded !== false ? $decoded : $value);
}

function decodeBody(string $body, array $headers): string
{
    $transferEncoding = strtolower($headers['content-transfer-encoding'] ?? '');

    if (str_contains($transferEncoding, 'base64')) {
        $decoded = base64_decode(preg_replace('/\s+/', '', $body) ?: '', true);

        if (is_string($decoded) && $decoded !== '') {
            return normalizeBody($decoded);
        }
    }

    if (str_contains($transferEncoding, 'quoted-printable')) {
        return normalizeBody(quoted_printable_decode($body));
    }

    return normalizeBody($body);
}

function normalizeBody(string $body): string
{
    $normalized = str_replace(["\r\n", "\r"], "\n", $body);
    $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return trim($normalized);
}

function createPreviewText(string $body): string
{
    $text = preg_replace('/\s+/', ' ', trim(strip_tags($body))) ?? '';

    return mb_strimwidth($text, 0, 220, '…', 'UTF-8');
}

function extractUrls(string $body): array
{
    preg_match_all('~https?://[^\s"\'<>]+~u', $body, $matches);

    return array_values(array_unique(array_map(
        static fn (string $url): string => rtrim($url, '.,;)'),
        $matches[0] ?? []
    )));
}
