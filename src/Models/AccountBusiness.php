<?php

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;
use MetaFramework\Polyglote\Traits\Translation;
use MetaFramework\Accounts\Support\AccountModel;

class AccountBusiness extends Model implements TranslatableInterface
{

    use Translation;

    protected $table = 'mfw_accounts_account_business';

    protected $fillable = [
        'user_id',
        'name',
        'vat_number',
        'reg_number',
        'is_seller',
        'seller_slug',
    ];

    protected $casts = [
        'is_seller' => 'boolean',
    ];

    public function setTranslatables(): array
    {
        return [
            'name' => __('mfw-accounts::ui.CompanyName'),
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::className(), 'user_id');
    }
}


