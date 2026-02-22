<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Accessors;

use MetaFramework\Accounts\Models\Account;

class AccountAccessor
{
    public function __construct(private readonly Account $account) {}

    public function shortName(?string $locale = null): string
    {
        $locale ??= $this->account->locale ?: config('app.fallback_locale');

        if ($this->account->isCompany()) {
            return (string) $this->account->business?->getTranslation('name', $locale);
        }

        $firstName = $this->account->getTranslation('first_name', $locale);
        $lastName = $this->account->getTranslation('last_name', $locale);

        return trim((string) ($firstName . ' ' . $lastName));
    }
}
