<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SellChannels extends Model
{
    protected $table = 'mfw_accounts_sell_channels';

    public $timestamps = false;

    public static function SellChannels(): Collection
    {
        return self::query()->pluck('name', 'id');
    }
}
