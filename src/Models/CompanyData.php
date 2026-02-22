<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Model;

class CompanyData extends Model
{
    protected $table = 'mfw_accounts_company_data';

    public $timestamps = false;

    protected $guarded = [];
}
