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
     * @var Collection<int, array{account_id:int, slug:string}>|null
     */
    private ?Collection $sellerAccounts = null;

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

                if (!is_array($config)) {
                    return null;
                }

                $slug = pathinfo($path, PATHINFO_FILENAME);
                $prices = $config['prices'] ?? [];

                return [
                    'slug' => trim((string)$slug),
                    'prices' => collect(is_array($prices) ? $prices : [])
                        ->map(fn (mixed $value): string => trim((string)$value))
                        ->filter(fn (string $value): bool => $value !== '')
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (?array $config): bool => is_array($config))
            ->values();

        return $this->sellerConfigs;
    }

    /**
     * @return Collection<int, array{account_id:int, slug:string}>
     */
    public function sellerAccounts(): Collection
    {
        if ($this->sellerAccounts instanceof Collection) {
            return $this->sellerAccounts;
        }

        $this->sellerAccounts = AccountBusiness::query()
            ->where('is_seller', true)
            ->get(['user_id', 'seller_slug'])
            ->map(function (AccountBusiness $business): array {
                return [
                    'account_id' => (int)$business->user_id,
                    'slug' => trim((string)$business->seller_slug),
                ];
            })
            ->filter(fn (array $seller): bool => $seller['account_id'] > 0 && $seller['slug'] !== '')
            ->values();

        return $this->sellerAccounts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function configForBusiness(?AccountBusiness $business): ?array
    {
        if (!$business || !(bool)($business->is_seller ?? false)) {
            return null;
        }

        $seller = $this->sellerAccounts()->first(function (array $seller) use ($business): bool {
            $accountId = (int)($business->user_id ?? 0);
            $sellerSlug = trim((string)($business->seller_slug ?? ''));

            if ($accountId > 0 && $seller['account_id'] === $accountId) {
                return true;
            }

            return $sellerSlug !== '' && $seller['slug'] === $sellerSlug;
        });

        if (!is_array($seller)) {
            return null;
        }

        $config = $this->sellerConfigs()->first(function (array $config) use ($seller): bool {
            return trim((string)($config['slug'] ?? '')) === $seller['slug'];
        });

        if (!is_array($config)) {
            return null;
        }

        return array_merge($config, $seller);
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

        return collect((array)($config['prices'] ?? []))
            ->map(fn (mixed $value): string => trim((string)$value))
            ->contains($type);
    }

    /**
     * @param  array<string, mixed>|null  $config
     */
    public function customPriceSellerId(?array $config, ?int $fallback = null): ?int
    {
        $sellerId = (int)($config['account_id'] ?? 0);

        if ($sellerId > 0) {
            return $sellerId;
        }

        return $fallback && $fallback > 0 ? $fallback : null;
    }

    public function resetCache(): void
    {
        $this->sellerConfigs = null;
        $this->sellerAccounts = null;
    }

    abstract protected function sellerConfigsGlob(): string;
}
