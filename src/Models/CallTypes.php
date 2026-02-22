<?php
namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;

class CallTypes extends Model {

    protected $object = null;
    protected $table = 'crm_call_types';
    public $timestamps = false;


    public static function CallTypes() {

    	return CallTypes::orderBy('order')->pluck('name','id');
    }


}
