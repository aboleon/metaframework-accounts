<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use App\Models\User;
use MetaFramework\Accounts\Enum\UserType;
use MetaFramework\Polyglote\Traits\Translation;
use MetaFramework\Traits\TypedUser;

class AccountUser extends User
{
    use Translation;
    use TypedUser;

    protected static function typedUserScopeType(): ?string
    {
        return UserType::ACCOUNT->value;
    }
}
