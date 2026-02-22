<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;

class AccountsConfig extends Model
{
    public static function Form(
        $class,
        $form_name,
        $value = null,
        $label = null,
        $option_value = null,
        $showNull = false
    ): string {
        $call = 'MetaFramework\\Accounts\\Models\\'.$class;
        $data = $call::orderBy('name')->get()->toArray();
        $html = "<select class='form-control' name='".$form_name."'>";

        if ($data) {
            $html .= "<option value='0'>".__('ui.optionChoose').'</option>';
        }

        foreach ($data as $key => $item) {
            $classe = [];
            $html .= "<option label='".$item[! empty($label) ? $label : 'name']."' value='".$item['id']."' class='";
            if (! empty($item['multiple'])) {
                $classe[] = 'multiple';
            }
            if (! empty($item['card_group'])) {
                $classe[] = 'card_group';
            }
            if ($classe) {
                $html .= implode(' ', $classe);
            }
            $html .= "'";
            if (! empty($value)) {
                if ($value == $item['id']) {
                    $html .= ' selected';
                }
            } else {
                if (! empty($item['default'])) {
                    $html .= ' selected';
                }
            }
            $html .= '>'.$item[! empty($option_value) ? $option_value : 'name'].'</option>';
        }

        $html .= '</select>';

        return $html;
    }
}
