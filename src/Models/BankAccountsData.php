<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Model;

class BankAccountsData extends Model
{

    public $timestamps = false;
    protected $table = 'mfw_accounts_bank_accounts_data';

    public function account() {
    	return $this->belongsTo(BankAccounts::class, 'account_id');
    }
}
