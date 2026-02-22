<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Model;
use MetaFramework\Traits\Locale;

final class PayMeansChannels extends Model
{

    use Locale;

    public $table = 'mfw_accounts_pay_means_channels';
    public $timestamps = false;
    protected $guarded = [];

    public function translation()
    {
        return $this->hasOne(PayMeansChannelsData::class, 'pay_channel_id')->where('lg', $this->locale());
    }

    public function translations()
    {
        return $this->hasMany(PayMeansChannelsData::class, 'pay_channel_id');
    }

    public function master()
    {
        return $this->belongsTo(PayMeans::class, 'pay_mean_id');
    }
}
