<?php

namespace MetaFramework\Accounts\Accessors;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use MetaFramework\Accounts\Models\Vat;

class VatAccessor
{
    public static function fetchVatRate(int $vat_id): int
    {
        return self::rate($vat_id);
    }

    public static function fetchDefaultVatRate(): int|float
    {
        return self::defaultRate()?->rate ?? 0;
    }

    public static function vatForPrice(null|float|int $price, int $vat_id): float|int
    {
        if (! $price) {
            return 0;
        }

        $vat_rate = VatAccessor::fetchVatRate($vat_id);

        return round($price / (100 + $vat_rate) * $vat_rate, 2);
    }

    public static function netPriceFromVatPrice(null|float|int $price, int $vat_id): float|int
    {
        if (! $price) {
            return 0;
        }

        return round($price - VatAccessor::vatForPrice($price, $vat_id), 2);
    }

    public static function vats(): Collection
    {
        return Cache::rememberForever('vats', fn () => Vat::query()
            ->orderByDesc('default')
            ->orderBy('rate')
            ->pluck('rate', 'id'));
    }

    public static function rate(?int $id): int|float
    {
        $rates = self::vats();

        if ($id !== null && $rates->has($id)) {
            return $rates->get($id);
        }

        return self::defaultRate()?->rate ?? 0;
    }

    public static function defaultRate(): ?Vat
    {
        return Cache::rememberForever('default_vat_rate', fn () => Vat::query()->where('default', 1)->first()
            ?? Vat::query()->orderBy('id')->first());
    }

    public static function defaultId(): int
    {
        return self::defaultRate()?->id ?? 0;
    }

    public static function readableArrayList(): array
    {
        return self::vats()->map(fn ($item) => $item.'%')->toArray();
    }

    public static function selectables(): array
    {
        return VatAccessor::readableArrayList();
    }

    public static function selectableOptionHtmlList($affected = null): string
    {
        $options = '';
        foreach (self::vats() as $key => $value) {
            $options .= '<option data-rate="'.$value.'" value="'.$key.'"'.($affected && $affected == $key ? ' selected' : '').'>'.$value.'%</option>'."\r\n";
        }

        return $options;
    }
}
