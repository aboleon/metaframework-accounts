<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Support;

use Carbon\Carbon;
use Throwable;

final class DateFormat
{
    public static function convert(?string $value, string $from, string $to): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat($from, $value)->format($to);
        } catch (Throwable) {
            return null;
        }
    }
}
