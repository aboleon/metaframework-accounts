<?php

declare(strict_types=1);

namespace App\Models;

class AccountUser extends User
{
    public static function localizedColumn(string $column, string $locale): string
    {
        return $column;
    }

    public function getTranslation(string $field, string $locale): ?string
    {
        $value = $this->getAttribute($field);
        if (is_array($value)) {
            return $value[$locale] ?? reset($value) ?: null;
        }

        if (!is_string($value) || trim($value) === '') {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $decoded[$locale] ?? reset($decoded) ?: null;
        }

        return $value;
    }

    public function setFirstNameAttribute(mixed $value): void
    {
        $this->attributes['first_name'] = $this->normalizeTranslatableValue($value);
    }

    public function setLastNameAttribute(mixed $value): void
    {
        $this->attributes['last_name'] = $this->normalizeTranslatableValue($value);
    }

    private function normalizeTranslatableValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
