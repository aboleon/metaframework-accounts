<?php

namespace MetaFramework\Accounts\Accessors;

use MetaFramework\Accounts\Models\PayMeans;

class Selectables {
    public static function payMeans(): array
    {
        return cache()->rememberForever('pay-means-'.app()->getLocale(), function () {
           return PayMeans::pluck('name', 'id')->toArray();
        });
    }
}
