<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Models\AccountBusiness;

abstract class AbstractSellerConfigResolver
{
    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    private ?Collection $sellerConfigs = null;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function sellerConfigs(): Collection
    {
        if ($this->sellerConfigs instanceof Collection) {
            return $this->sellerConfigs;
        }

        $paths = File::glob($this->sellerConfigsGlob()) ?: [];

        $this->sellerConfigs = collect($paths)
            ->map(function (string $path): ?array {
                $config = require $path;

                return is_array($config) ? $config : null;
            })
            ->filter(fn (?array $config): bool => is_array($config))
            ->values();

        return $this->sellerConfigs;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function configForBusiness(?AccountBusiness $business): ?array
    {
        if (!$business || !(bool)($business->is_seller ?? false)) {
            return null;
        }

        $accountId = (int)($business->user_id ?? 0);
        $sellerSlug = trim((string)($business->seller_slug ?? ''));

        return $this->sellerConfigs()->first(function (array $config) use ($accountId, $sellerSlug): bool {
            $configAccountId = (int)($config['account_id'] ?? 0);
            $configSlug = trim((string)($config['slug'] ?? ''));

            if ($configAccountId > 0 && $configAccountId === $accountId) {
                return true;
            }

            return $sellerSlug !== '' && $configSlug === $sellerSlug;
        });
    }

    /**
     * @param  iterable<int, int|string|null>  $accountIds
     * @return array<string, mixed>|null
     */
    public function configForAccountIds(iterable $accountIds): ?array
    {
        $normalizedAccountIds = collect($accountIds)
            ->map(fn (mixed $accountId): int => (int)$accountId)
            ->filter(fn (int $accountId): bool => $accountId > 0)
            ->values();

        if ($normalizedAccountIds->isEmpty()) {
            return null;
        }

        $accounts = Account::query()
            ->with('business')
            ->whereIn('id', $normalizedAccountIds->all())
            ->get()
            ->keyBy('id');

        foreach ($normalizedAccountIds as $accountId) {
            $account = $accounts->get($accountId);
            if (!$account) {
                continue;
            }

            $config = $this->configForBusiness($account->business);
            if (is_array($config)) {
                return $config;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $config
     */
    public function hasCustomPriceType(?array $config, string $type): bool
    {
        if (!is_array($config)) {
            return false;
        }

        $customPrices = data_get($config, 'customConfig.customPrices', data_get($config, 'customPrices', []));
        if (!is_array($customPrices)) {
            return false;
        }

        if (array_is_list($customPrices)) {
            return collect($customPrices)
                ->map(fn (mixed $value): string => trim((string)$value))
                ->contains($type);
        }

        if (!array_key_exists($type, $customPrices)) {
            return false;
        }

        $typeConfig = $customPrices[$type];
        if (is_array($typeConfig)) {
            return (bool)($typeConfig['enabled'] ?? true);
        }

        return filter_var($typeConfig, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool)$typeConfig;
    }

    public function resetCache(): void
    {
        $this->sellerConfigs = null;
    }

    abstract protected function sellerConfigsGlob(): string;
}
