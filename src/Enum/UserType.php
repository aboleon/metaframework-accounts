<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Enum;

use MetaFramework\Interfaces\BackedEnumInteface;
use MetaFramework\Traits\BackedEnum;

enum UserType: string implements BackedEnumInteface
{
    use BackedEnum;

    case SYSTEM = 'system';
    case ACCOUNT = 'account';

    public static function default(): string
    {
        return self::SYSTEM->value;
    }
}
