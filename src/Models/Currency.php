<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Currency extends Model
{
    protected $table = 'mfw_accounts_currencies';

    public $timestamps = false;

    public static function getCurrenciesSigns(): Collection
    {
        return self::query()->orderByDesc('default')->pluck('sign', 'id');
    }

    public static function getCurrencies(): Collection
    {
        return self::query()->orderByDesc('code')->get();
    }
}
