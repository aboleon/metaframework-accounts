<?php

namespace MetaFramework\Accounts\Accessors;

use MetaFramework\Accounts\Models\SellChannels;

class SellableChannelsAccessor
{
    public static function selectables(): array
    {
        return cache()->rememberForever('sellableChannels', fn () => SellChannels::pluck('name', 'id')->toArray());
    }

    public static function defaultChannelId(): int
    {
        return (int) cache()->rememberForever(
            'sellableChannelDefaultId',
            fn () => SellChannels::where('default', 1)->value('id') ?? 0
        );
    }
}
