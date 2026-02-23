<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Accessors;

use MetaFramework\GooglePlaces\Accessors\Country;

final class AddressAccessor
{
    public static function printLocaleAddress(
        object $address,
        ?string $locale = null,
        bool $extended = false,
    ): string {
        $locale = $locale ?? app()->getLocale();

        $parts = [
            $address->street_number ?? null,
            self::translatedField($address, 'route', $locale),
            $address->postal_code ?? null,
            self::translatedField($address, 'locality', $locale),
        ];

        if ($extended) {
            $parts[] = self::translatedField($address, 'administrative_area_level_2', $locale);
            $parts[] = self::translatedField($address, 'administrative_area_level_1', $locale);
        }

        $parts[] = Country::getCountryNameByCodeAndLocale((string) ($address->country_code ?? ''));

        return implode(', ', array_filter($parts, static fn ($value) => $value !== null && $value !== ''));
    }

    private static function translatedField(object $address, string $field, string $locale): ?string
    {
        if (method_exists($address, 'translation')) {
            $value = $address->translation($field, $locale);

            return is_string($value) ? $value : null;
        }

        if (method_exists($address, 'getTranslation')) {
            $value = $address->getTranslation($field, $locale);

            return is_string($value) ? $value : null;
        }

        if (method_exists($address, 'getTranslations')) {
            $translations = $address->getTranslations($field);

            if (is_array($translations) && array_key_exists($locale, $translations)) {
                return is_string($translations[$locale]) ? $translations[$locale] : null;
            }
        }

        $value = $address->{$field} ?? null;

        if (is_array($value)) {
            $value = $value[$locale] ?? null;
        }

        return is_string($value) ? $value : null;
    }
}
