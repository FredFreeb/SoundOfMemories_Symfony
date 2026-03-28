<?php

namespace App\Service;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class PhoneNumberService
{
    private const REGION_LABELS = [
        'FR' => 'France',
        'BE' => 'Belgique',
        'CH' => 'Suisse',
        'LU' => 'Luxembourg',
        'DE' => 'Allemagne',
        'AT' => 'Autriche',
        'ES' => 'Espagne',
        'IT' => 'Italie',
        'PT' => 'Portugal',
        'NL' => 'Pays-Bas',
        'IE' => 'Irlande',
        'GB' => 'Royaume-Uni',
        'CZ' => 'Tchéquie',
        'SK' => 'Slovaquie',
        'PL' => 'Pologne',
        'SE' => 'Suède',
        'DK' => 'Danemark',
        'FI' => 'Finlande',
        'NO' => 'Norvège',
        'US' => 'États-Unis',
        'CA' => 'Canada',
    ];

    private PhoneNumberUtil $phoneNumberUtil;

    public function __construct()
    {
        $this->phoneNumberUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * @return array<string, string>
     */
    public function getCountryChoices(): array
    {
        $choices = [];

        foreach (self::REGION_LABELS as $region => $label) {
            $choices[sprintf('%s %s (+%d)', $this->getFlagEmoji($region), $label, $this->getDialCode($region))] = $region;
        }

        return $choices;
    }

    public function getDialCode(string $region): int
    {
        return $this->phoneNumberUtil->getCountryCodeForRegion($region);
    }

    public function getRegionLabel(string $region): string
    {
        return self::REGION_LABELS[$region] ?? $region;
    }

    /**
     * @return list<string>
     */
    public function getSupportedRegions(): array
    {
        return array_keys(self::REGION_LABELS);
    }

    public function getFlagEmoji(string $region): string
    {
        $region = strtoupper(trim($region));

        if (2 !== strlen($region)) {
            return '';
        }

        return mb_chr(127397 + ord($region[0])).mb_chr(127397 + ord($region[1]));
    }

    public function detectRegion(?string $phoneNumber, string $defaultRegion = 'FR'): string
    {
        $normalized = trim((string) $phoneNumber);

        if ('' === $normalized) {
            return $defaultRegion;
        }

        try {
            $parsed = $this->phoneNumberUtil->parse($normalized, $defaultRegion);
            $region = $this->phoneNumberUtil->getRegionCodeForNumber($parsed);

            return \is_string($region) && '' !== $region ? $region : $defaultRegion;
        } catch (NumberParseException) {
            return $defaultRegion;
        }
    }

    public function getLocalNumber(?string $phoneNumber, string $defaultRegion = 'FR'): string
    {
        $normalized = trim((string) $phoneNumber);

        if ('' === $normalized) {
            return '';
        }

        try {
            $parsed = $this->phoneNumberUtil->parse($normalized, $defaultRegion);

            if ($this->phoneNumberUtil->isValidNumber($parsed)) {
                return $this->phoneNumberUtil->format($parsed, PhoneNumberFormat::NATIONAL);
            }
        } catch (NumberParseException) {
        }

        return $normalized;
    }

    public function normalize(?string $region, ?string $phoneNumber): ?string
    {
        $normalized = trim((string) $phoneNumber);

        if ('' === $normalized) {
            return null;
        }

        $region = $region ?: 'FR';

        try {
            $parsed = $this->phoneNumberUtil->parse($normalized, $region);
        } catch (NumberParseException) {
            throw new \InvalidArgumentException('Le numéro de téléphone ne correspond pas à l’indicatif sélectionné.');
        }

        if (!$this->phoneNumberUtil->isValidNumberForRegion($parsed, $region)) {
            throw new \InvalidArgumentException('Le numéro de téléphone ne correspond pas à l’indicatif sélectionné.');
        }

        $nationalNumber = (string) $parsed->getNationalNumber();

        if ($this->looksObviouslyFake($nationalNumber)) {
            throw new \InvalidArgumentException('Le numéro semble factice. Merci d’utiliser un numéro réel.');
        }

        return $this->phoneNumberUtil->format($parsed, PhoneNumberFormat::E164);
    }

    public function guessRegionForInput(?string $phoneNumber, string $preferredRegion = 'FR'): ?string
    {
        $normalized = trim((string) $phoneNumber);

        if ('' === $normalized) {
            return null;
        }

        if (str_starts_with($normalized, '+') || str_starts_with($normalized, '00')) {
            $region = $this->detectRegion($normalized, $preferredRegion);

            return in_array($region, $this->getSupportedRegions(), true) ? $region : null;
        }

        $candidates = $this->findMatchingRegions($normalized);

        if ([] === $candidates) {
            return null;
        }

        return 1 === count($candidates) ? $candidates[0] : null;
    }

    public function formatForDisplay(?string $phoneNumber, string $defaultRegion = 'FR'): ?string
    {
        $normalized = trim((string) $phoneNumber);

        if ('' === $normalized) {
            return null;
        }

        try {
            $parsed = $this->phoneNumberUtil->parse($normalized, $defaultRegion);

            if ($this->phoneNumberUtil->isValidNumber($parsed)) {
                $region = $this->phoneNumberUtil->getRegionCodeForNumber($parsed);
                $localNumber = $this->phoneNumberUtil->format($parsed, PhoneNumberFormat::NATIONAL);

                if (\is_string($region) && '' !== $region) {
                    return trim(sprintf('%s %s', $this->getFlagEmoji($region), $localNumber));
                }

                return $localNumber;
            }
        } catch (NumberParseException) {
        }

        return $normalized;
    }

    private function isValidForRegion(string $region, string $phoneNumber): bool
    {
        try {
            $parsed = $this->phoneNumberUtil->parse($phoneNumber, $region);

            return $this->phoneNumberUtil->isValidNumberForRegion($parsed, $region);
        } catch (NumberParseException) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    public function findMatchingRegions(?string $phoneNumber): array
    {
        $normalized = trim((string) $phoneNumber);

        if ('' === $normalized) {
            return [];
        }

        $matches = [];

        foreach ($this->getSupportedRegions() as $region) {
            if ($this->isValidForRegion($region, $normalized)) {
                $matches[] = $region;
            }
        }

        return $matches;
    }

    private function looksObviouslyFake(string $nationalNumber): bool
    {
        $digits = preg_replace('/\D+/', '', $nationalNumber) ?? '';

        if (strlen($digits) < 6) {
            return true;
        }

        if (preg_match('/^(\d)\1{5,}$/', $digits) === 1) {
            return true;
        }

        if ($this->isSequentialDigits($digits)) {
            return true;
        }

        foreach ([2, 3, 4] as $chunkLength) {
            if (strlen($digits) >= $chunkLength * 3 && 0 === strlen($digits) % $chunkLength) {
                $chunk = substr($digits, 0, $chunkLength);

                if (str_repeat($chunk, (int) (strlen($digits) / $chunkLength)) === $digits) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isSequentialDigits(string $digits): bool
    {
        $ascending = '01234567890123456789';
        $descending = '98765432109876543210';

        return str_contains($ascending, $digits) || str_contains($descending, $digits);
    }
}
