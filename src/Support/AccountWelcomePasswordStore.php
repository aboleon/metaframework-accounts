<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Services\Passwords\PasswordGenerator;

class AccountWelcomePasswordStore
{
    private const int TTL_MINUTES = 30;

    /**
     * @return array{token:string,password:string,hashed_password:string}
     */
    public function issue(Account $account): array
    {
        $generator = new PasswordGenerator;
        $generator->generateRandomPublicPassword()->hashPassword();

        $payload = [
            'account_id' => $account->id,
            'password' => $generator->getPublicPassword(),
            'hashed_password' => $generator->getEncryptedPassword(),
        ];

        $token = Str::random(40);

        Cache::put($this->cacheKey($token), $payload, now()->addMinutes(self::TTL_MINUTES));

        return [
            'token' => $token,
            'password' => $payload['password'],
            'hashed_password' => $payload['hashed_password'],
        ];
    }

    /**
     * @return array{account_id:int,password:string,hashed_password:string}|null
     */
    public function retrieve(Account $account, string $token): ?array
    {
        $payload = Cache::get($this->cacheKey($token));

        if (!is_array($payload)) {
            return null;
        }

        if ((int) ($payload['account_id'] ?? 0) !== (int) $account->id) {
            return null;
        }

        $password = (string) ($payload['password'] ?? '');
        $hashedPassword = (string) ($payload['hashed_password'] ?? '');

        if ($password === '' || $hashedPassword === '') {
            return null;
        }

        return [
            'account_id' => (int) $payload['account_id'],
            'password' => $password,
            'hashed_password' => $hashedPassword,
        ];
    }

    public function forget(string $token): void
    {
        Cache::forget($this->cacheKey($token));
    }

    private function cacheKey(string $token): string
    {
        return 'mfw-accounts:welcome-password:' . $token;
    }
}
