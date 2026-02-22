<?php

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MetaFramework\Inputable\Contracts\GooglePlacesInterface;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;
use MetaFramework\Polyglote\Traits\Translation;

/**
 * @property string $country_code;
 * @property string $locality;/**
 *
 * @mixin Model
 * /
 */
class AccountAddress extends Model implements TranslatableInterface
{

    use Translation;

    protected $fillable = [
        'company',
        'postal_code',
        'country_code',
        'street_number',
        'locality',
        'cedex',
        'route',
        'administrative_area_level_1',
        'administrative_area_level_2',
        'lat',
        'lon',
        'name',
        'complementary',
        'billing',
        'text_address',
        'place_id',
    ];

    protected $table = 'mfw_accounts_account_address';

    public function setTranslatables(): array
    {
        return [
            'route' => __('mfw-inputable-geo.route'),
            'locality' => __('mfw-inputable-geo.locality'),
            'administrative_area_level_1' => __('mfw-inputable-geo.region'),
            'administrative_area_level_2' => __('mfw-inputable-geo.district'),
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($address) {
            if ($address->billing == 1 && $address->account) {
                $address->account->address()->where('id', '!=', $address->id)->update(['billing' => null]);
            }
        });

        static::deleted(function ($address) {
            if ($address->billing == 1) {
                $newBillingAddress = $address->account->address()
                    ->whereNotNull('company')
                    ->orderBy('id')
                    ->first();
                if (! $newBillingAddress) {
                    $newBillingAddress = $address->account->address()
                        ->orderBy('id')
                        ->first();
                }
                if ($newBillingAddress) {
                    $newBillingAddress->billing = 1;
                    $newBillingAddress->save();
                }
            }
        });

    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'user_id');
    }
}
