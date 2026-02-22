<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;

class PayMeansChannelsData extends Model
{
    public $table = 'mfw_accounts_pay_means_channels_data';

    public $timestamps = false;

    protected $guarded = [];
}
