<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use MetaFramework\Accounts\Enum\UserType;

class AccountAgent extends Account
{
    protected static function typedUserScopeType(): array|string|null
    {
        return UserType::AGENT->value;
    }

    protected static function typedUserCreateType(): array|string|null
    {
        return UserType::AGENT->value;
    }
}
