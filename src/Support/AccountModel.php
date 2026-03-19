<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Support;

use MetaFramework\Accounts\Models\Account;

class AccountModel
{
    /**
     * @return class-string<Account>
     */
    public static function className(): string
    {
        $configured = config('mfw-accounts.models.account');

        if (is_string($configured) && $configured !== '' && is_a($configured, Account::class, true)) {
            return $configured;
        }

        return Account::class;
    }

    public static function localizedColumn(string $column, ?string $locale = null): string
    {
        $accountClass = self::className();

        return $accountClass::localizedColumn($column, $locale);
    }
}
