<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Support;

use NumberFormatter;
use Throwable;

final class BulgarianWords
{
    public static function toWords(int $number, bool $cents = false): string
    {
        try {
            if (class_exists(NumberFormatter::class)) {
                $formatter = new NumberFormatter('bg', NumberFormatter::SPELLOUT);
                $formatted = $formatter->format($number);

                if (is_string($formatted) && trim($formatted) !== '') {
                    return trim($formatted);
                }
            }
        } catch (Throwable) {
        }

        unset($cents);

        return (string) $number;
    }
}
