<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;
use MetaFramework\Polyglote\Traits\Translation;

final class PayMeansChannels extends Model implements TranslatableInterface
{
    use Translation;

    public $table = 'mfw_accounts_pay_means_channels';

    public $timestamps = false;

    protected $fillable = [
        'pay_mean_id',
        'bank_account_id',
        'name',
    ];

    public function setTranslatables(): array
    {
        return [
            'name' => [
                'label' => __('mfw-accounts::ui.title'),
            ],
        ];
    }

    public function master()
    {
        return $this->belongsTo(PayMeans::class, 'pay_mean_id');
    }
}

