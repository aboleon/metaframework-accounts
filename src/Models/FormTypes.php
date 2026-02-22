<?php
namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;

class FormTypes extends Model {

    protected $object = null;
    protected $table = 'crm_form_types';


    public static function FormTypes() {

    	return self::pluck('name','id');
    }


}
