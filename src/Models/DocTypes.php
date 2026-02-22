<?php
namespace MetaFramework\Accounts\Models;


class DocTypes extends Illuminate\Database\Eloquent\Model {

    protected $object = null;
    protected $table = 'mfw_accounts_doc_types';
    public $timestamps = false;


    public static function simpleList() {

    	return DocTypes::pluck('name','id');
    }

}