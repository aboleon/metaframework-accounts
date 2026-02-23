<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Accessors;

use MetaFramework\Accessors\Locale;
use MetaFramework\Accounts\Models\Account;

class AccountAccessor
{
    public function __construct(private readonly Account $account) {}

    public function shortName(?string $locale = null): string
    {
        $locale ??= $this->account->locale ?: config('mfw.translatable.fallback_locale', config('app.fallback_locale'));

        if ($this->account->isCompany()) {
            return (string) $this->account->business?->translation('name', $locale);
        }

        $firstName = $this->account->translation('first_name', $locale);
        $lastName = $this->account->translation('last_name', $locale);

        if (!Locale::multilang() && $firstName === '' && $lastName === '') {
            return trim((string) ($this->account->first_name . ' ' . $this->account->last_name));
        }

        return trim((string) ($firstName . ' ' . $lastName));
    }
}
