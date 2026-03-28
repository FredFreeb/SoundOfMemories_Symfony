<?php

namespace App\Service;

final class BoxtalShippingService
{
    private const API_BASE_TEST = 'https://test.envoimoinscher.com/api/v1';
    private const API_BASE_PRODUCTION = 'https://www.envoimoinscher.com/api/v1';

    /**
     * Fred note: Je garde une liste europeenne utile au projet pour simplifier le checkout.
     *
     * @var array<string, string>
     */
    private const DESTINATION_COUNTRIES = [
        'République tchèque' => 'CZ',
        'Slovaquie' => 'SK',
        'France' => 'FR',
        'Belgique' => 'BE',
        'Luxembourg' => 'LU',
        'Pays-Bas' => 'NL',
        'Allemagne' => 'DE',
        'Autriche' => 'AT',
        'Pologne' => 'PL',
        'Hongrie' => 'HU',
        'Slovénie' => 'SI',
        'Croatie' => 'HR',
        'Italie' => 'IT',
        'Espagne' => 'ES',
        'Portugal' => 'PT',
        'Irlande' => 'IE',
        'Danemark' => 'DK',
        'Suède' => 'SE',
        'Finlande' => 'FI',
        'Norvège' => 'NO',
        'Suisse' => 'CH',
        'Royaume-Uni' => 'GB',
    ];

    public function __construct(
        private readonly bool $boxtalEnabled,
        private readonly bool $boxtalTestMode,
        private readonly string $boxtalUser,
        private readonly string $boxtalPassword,
        private readonly string $boxtalContentCode,
        private readonly string $boxtalFromCountry,
        private readonly string $boxtalFromPostalCode,
        private readonly int $boxtalDefaultParcelWeightGrams,
        private readonly int $boxtalExtraItemWeightGrams,
        private readonly int $boxtalParcelLengthCm,
        private readonly int $boxtalParcelWidthCm,
        private readonly int $boxtalParcelHeightCm,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getDestinationCountryChoices(): array
    {
        return self::DESTINATION_COUNTRIES;
    }

    public function isLiveConfigured(): bool
    {
        return $this->boxtalEnabled
            && '' !== trim($this->boxtalUser)
            && '' !== trim($this->boxtalPassword)
            && '' !== trim($this->boxtalContentCode)
            && '' !== trim($this->boxtalFromCountry)
            && '' !== trim($this->boxtalFromPostalCode);
    }

    /**
     * @param array<int, array{quantity:int}> $items
     *
     * @return array<int, array{code:string,label:string,description:string,priceCents:int,carrier:string,provider:string,deliveryType:string,live:bool,estimated:bool}>
     */
    public function quoteCheckoutOptions(?string $countryCode, ?string $postalCode, array $items): array
    {
        $countryCode = $this->normalizeCountryCode($countryCode);

        if ('' === $countryCode || !in_array($countryCode, array_values(self::DESTINATION_COUNTRIES), true)) {
            return [];
        }

        if ($this->isLiveConfigured()) {
            try {
                $liveOptions = $this->fetchLiveDoorToDoorOptions($countryCode, $postalCode, $items);

                if ([] !== $liveOptions) {
                    return $liveOptions;
                }
            } catch (\Throwable) {
                // Fred note: Je degrade vers les tarifs internes pour ne pas bloquer le checkout si Boxtal ne repond pas encore.
            }
        }

        return $this->buildFallbackDoorToDoorOptions($countryCode, $this->countItems($items));
    }

    /**
     * @param array<int, array{code:string,label:string,description:string,priceCents:int,carrier:string,provider:string,deliveryType:string,live:bool,estimated:bool}> $options
     */
    public function getCheckoutHint(?string $countryCode, array $options): string
    {
        $countryCode = $this->normalizeCountryCode($countryCode);

        if ('' === $countryCode) {
            return 'Choisissez le pays de livraison pour afficher les modes disponibles.';
        }

        if ([] === $options) {
            return 'Aucun mode de livraison n’est disponible pour cette destination pour le moment.';
        }

        if ($this->isLiveConfigured() && false === $options[0]['estimated']) {
            return 'Les tarifs sont récupérés depuis Boxtal pour cette destination.';
        }

        return 'Les tarifs affichés sont une estimation interne tant que les identifiants Boxtal ne sont pas encore actifs.';
    }

    /**
     * @param array<int, array{code:string,label:string,description:string,priceCents:int,carrier:string,provider:string,deliveryType:string,live:bool,estimated:bool}> $options
     *
     * @return array{code:string,label:string,description:string,priceCents:int,carrier:string,provider:string,deliveryType:string,live:bool,estimated:bool}|null
     */
    public function findOption(?string $code, array $options): ?array
    {
        if (null === $code || '' === trim($code)) {
            return null;
        }

        foreach ($options as $option) {
            if ($option['code'] === $code) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @param array<int, array{code:string,label:string,description:string,priceCents:int,carrier:string,provider:string,deliveryType:string,live:bool,estimated:bool}> $options
     *
     * @return array{code:string,label:string,description:string,priceCents:int,carrier:string,provider:string,deliveryType:string,live:bool,estimated:bool}|null
     */
    public function findCheapestOption(array $options): ?array
    {
        if ([] === $options) {
            return null;
        }

        usort($options, static fn (array $left, array $right): int => $left['priceCents'] <=> $right['priceCents']);

        return $options[0] ?? null;
    }

    /**
     * @param array<int, array{quantity:int}> $items
     */
    public function estimateParcelWeightGrams(array $items): int
    {
        $count = max(1, $this->countItems($items));

        return $this->boxtalDefaultParcelWeightGrams + max(0, $count - 1) * $this->boxtalExtraItemWeightGrams;
    }

    /**
     * @param array<int, array{quantity:int}> $items
     *
     * @return array<int, array{code:string,label:string,description:string,priceCents:int,carrier:string,provider:string,deliveryType:string,live:bool,estimated:bool}>
     */
    private function fetchLiveDoorToDoorOptions(string $countryCode, ?string $postalCode, array $items): array
    {
        $payload = $this->requestXml('/cotation', [
            'shipper.country' => $this->normalizeCountryCode($this->boxtalFromCountry),
            'shipper.zipcode' => trim($this->boxtalFromPostalCode),
            'recipient.country' => $countryCode,
            'recipient.zipcode' => trim((string) $postalCode),
            'colis_1.poids' => $this->formatWeightKilograms($this->estimateParcelWeightGrams($items)),
            'colis_1.longueur' => max(1, $this->boxtalParcelLengthCm),
            'colis_1.largeur' => max(1, $this->boxtalParcelWidthCm),
            'colis_1.hauteur' => max(1, $this->boxtalParcelHeightCm),
            'content_code' => trim($this->boxtalContentCode),
            'delay' => 'aucun',
        ]);

        if (null === $payload) {
            return [];
        }

        $offers = $payload->xpath('/cotation/shipment/offer');

        if (!is_array($offers)) {
            return [];
        }

        $options = [];

        foreach ($offers as $offer) {
            if (!$offer instanceof \SimpleXMLElement) {
                continue;
            }

            $deliveryType = mb_strtolower(trim((string) ($offer->delivery->type ?? '')));

            if ($this->looksLikeRelayDelivery($deliveryType)) {
                continue;
            }

            $price = trim((string) ($offer->price->{'tax-inclusive'} ?? ''));

            if ('' === $price || !is_numeric(str_replace(',', '.', $price))) {
                continue;
            }

            $carrier = trim((string) ($offer->operator->label ?? 'Boxtal'));
            $serviceLabel = trim((string) ($offer->service->label ?? 'Livraison à domicile'));
            $operatorCode = trim((string) ($offer->operator->code ?? 'boxtal'));
            $serviceCode = trim((string) ($offer->service->code ?? md5($carrier . $serviceLabel)));
            $label = $serviceLabel !== '' ? $serviceLabel : $carrier;

            $options[] = [
                'code' => sprintf('boxtal:%s:%s', $operatorCode !== '' ? $operatorCode : 'carrier', $serviceCode),
                'label' => $label,
                'description' => $this->buildLiveDescription($offer, $postalCode),
                'priceCents' => (int) round(((float) str_replace(',', '.', $price)) * 100),
                'carrier' => $carrier !== '' ? $carrier : 'Boxtal',
                'provider' => 'boxtal',
                'deliveryType' => 'home',
                'live' => true,
                'estimated' => false,
            ];
        }

        usort($options, static function (array $left, array $right): int {
            $priceComparison = $left['priceCents'] <=> $right['priceCents'];

            if (0 !== $priceComparison) {
                return $priceComparison;
            }

            return strcasecmp($left['label'], $right['label']);
        });

        return array_slice($options, 0, 6);
    }

    /**
     * @param array<string, scalar|null> $query
     */
    private function requestXml(string $path, array $query): ?\SimpleXMLElement
    {
        $baseUrl = $this->boxtalTestMode ? self::API_BASE_TEST : self::API_BASE_PRODUCTION;
        $url = rtrim($baseUrl, '/') . $path;
        $queryString = http_build_query(array_filter($query, static fn ($value): bool => null !== $value && '' !== trim((string) $value)));

        if ('' !== $queryString) {
            $url .= '?' . $queryString;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'ignore_errors' => true,
                'header' => implode("\r\n", [
                    'Accept: application/xml',
                    'Accept-Language: fr-FR',
                    'Api-Version: 1.3.7',
                    'Authorization: ' . base64_encode($this->boxtalUser . ':' . $this->boxtalPassword),
                ]),
            ],
            'ssl' => [
                'verify_peer' => !$this->boxtalTestMode,
                'verify_peer_name' => !$this->boxtalTestMode,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);

        if (false === $raw || '' === trim($raw)) {
            return null;
        }

        try {
            $xml = new \SimpleXMLElement($raw);
        } catch (\Throwable) {
            return null;
        }

        if ('error' === $xml->getName()) {
            return null;
        }

        return $xml;
    }

    /**
     * @return array<int, array{code:string,label:string,description:string,priceCents:int,carrier:string,provider:string,deliveryType:string,live:bool,estimated:bool}>
     */
    private function buildFallbackDoorToDoorOptions(string $countryCode, int $itemCount): array
    {
        $zone = $this->resolveZone($countryCode);
        $suffix = $itemCount > 1 ? 'Colis renforcé pour plusieurs expéditions.' : 'Colis standard avec suivi.';

        $matrix = match ($zone) {
            'domestic' => [
                ['label' => 'Standard domicile', 'description' => '2 à 4 jours ouvrés. ' . $suffix, 'priceCents' => 690],
                ['label' => 'Express domicile', 'description' => '1 à 2 jours ouvrés. ' . $suffix, 'priceCents' => 990],
            ],
            'nearby' => [
                ['label' => 'Standard Europe proche', 'description' => '3 à 5 jours ouvrés. ' . $suffix, 'priceCents' => 890],
                ['label' => 'Prioritaire Europe proche', 'description' => '2 à 4 jours ouvrés. ' . $suffix, 'priceCents' => 1290],
            ],
            'europe' => [
                ['label' => 'Standard Europe', 'description' => '4 à 7 jours ouvrés. ' . $suffix, 'priceCents' => 1190],
            ],
            default => [
                ['label' => 'International suivi', 'description' => '5 à 9 jours ouvrés. ' . $suffix, 'priceCents' => 1590],
            ],
        };

        return array_map(static fn (array $row): array => [
            'code' => 'boxtal-fallback:' . md5($countryCode . $row['label']),
            'label' => $row['label'],
            'description' => $row['description'],
            'priceCents' => $row['priceCents'],
            'carrier' => 'Boxtal',
            'provider' => 'boxtal',
            'deliveryType' => 'home',
            'live' => false,
            'estimated' => true,
        ], $matrix);
    }

    private function resolveZone(string $countryCode): string
    {
        $fromCountry = $this->normalizeCountryCode($this->boxtalFromCountry);

        if ('' !== $fromCountry && $countryCode === $fromCountry) {
            return 'domestic';
        }

        if (in_array($countryCode, ['CZ', 'SK', 'DE', 'AT', 'PL', 'HU'], true)) {
            return 'nearby';
        }

        if (in_array($countryCode, ['FR', 'BE', 'NL', 'LU', 'IT', 'ES', 'PT', 'IE', 'DK', 'SE', 'FI', 'SI', 'HR'], true)) {
            return 'europe';
        }

        return 'international';
    }

    private function buildLiveDescription(\SimpleXMLElement $offer, ?string $postalCode): string
    {
        $parts = [];
        $deliveryLabel = trim((string) ($offer->delivery->label ?? ''));

        if ('' !== $deliveryLabel) {
            $parts[] = $deliveryLabel;
        }

        if (null !== $postalCode && '' !== trim($postalCode)) {
            $parts[] = sprintf('Destination %s.', trim($postalCode));
        }

        $parts[] = 'Livraison à domicile avec suivi.';

        return implode(' ', $parts);
    }

    private function looksLikeRelayDelivery(string $deliveryType): bool
    {
        if ('' === $deliveryType) {
            return false;
        }

        foreach (['relay', 'parcel', 'point', 'pickup', 'relais', 'locker', 'depot'] as $needle) {
            if (str_contains($deliveryType, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function formatWeightKilograms(int $grams): string
    {
        return number_format(max(1, $grams) / 1000, 2, '.', '');
    }

    private function normalizeCountryCode(?string $countryCode): string
    {
        return mb_strtoupper(trim((string) $countryCode));
    }

    /**
     * @param array<int, array{quantity:int}> $items
     */
    private function countItems(array $items): int
    {
        return max(0, array_reduce($items, static fn (int $carry, array $item): int => $carry + max(0, (int) ($item['quantity'] ?? 0)), 0));
    }
}
