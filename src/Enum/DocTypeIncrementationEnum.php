<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Enum;

use MetaFramework\Interfaces\BackedEnumInteface;
use MetaFramework\Traits\BackedEnum;

enum DocTypeIncrementationEnum: string implements BackedEnumInteface
{
    use BackedEnum;

    case GENERIC = 'generic';
    case OWN = 'own';

    public static function default(): string
    {
        return self::GENERIC->value;
    }

    public static function translationPrefix(): string
    {
        return 'mfw-accounts::';
    }
}
