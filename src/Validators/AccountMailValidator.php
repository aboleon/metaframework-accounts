<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Validators;

use Illuminate\Support\Str;
use MetaFramework\Accounts\Models\Account;

class AccountMailValidator
{
    public function validate(Account|int $account): array
    {
        $resolved = is_int($account) ? Account::query()->find($account) : $account;

        if (!$resolved) {
            return [
                'email' => '',
                'is_valid' => false,
                'is_fake' => false,
                'account' => null,
            ];
        }

        $email = trim((string) ($resolved->email ?? ''));
        $isFake = $email !== '' && Str::startsWith($email, 'random_');
        $isValid = $email !== ''
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && !$isFake;

        return [
            'email' => $email,
            'is_valid' => $isValid,
            'is_fake' => $isFake,
            'account' => $resolved,
        ];
    }
}
