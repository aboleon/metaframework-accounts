<?php namespace MetaFramework\Accounts\Models;


use Illuminate\Support\{Arr, Str};
use Illuminate\Database\Eloquent\Model;

class CashflowStructure extends Model {

    protected $object = null;
    protected $table = 'mfw_accounts_cashflow_structure';
    public $timestamps = false;

	// HAS FUNCTIONS
    public function invoice()
    {
      return $this->hasOne('MetaFramework\Accounts\Models\Invoice','id', 'invoice_id')->with('client');
    }


}
