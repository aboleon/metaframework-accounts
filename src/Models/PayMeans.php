<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;
use MetaFramework\Polyglote\Traits\Translation;

final class PayMeans extends Model implements TranslatableInterface
{

    use Translation;

    public $timestamps = false;
    protected $table = 'mfw_accounts_pay_means';

    protected $fillable = [
        'name',
        'type',
    ];

    public function setTranslatables(): array
    {
        return [
            'name' => [
                'label' => __('mfw-accounts::ui.Name'),
            ],
        ];
    }

    public static function fetchPayMeansByLocale(?string $locale = null): EloquentCollection
    {
        $locale = $locale ?? app()->getLocale();

        return self::query()
            ->select('id', 'name', 'type')
            ->get()
            ->map(function (self $payMean) use ($locale) {
                $payMean->setAttribute('name', $payMean->translation('name', $locale));
                $payMean->pay_mean_id = $payMean->id;

                return $payMean;
            })
            ->keyBy('pay_mean_id');
    }

    public function channels(): HasMany
    {
        return $this->hasMany(PayMeansChannels::class, 'pay_mean_id');
    }
}
