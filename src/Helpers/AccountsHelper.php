<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Helpers;

use MetaFramework\Accounts\Models\CashflowDocTypes;

final class AccountsHelper
{
    public static function selectDocTypes(mixed $type = null, bool $nullValue = false, array $exclude = []): string
    {
        return CashflowDocTypes::select_form($type, $nullValue, $exclude);
    }

    public static function comparisonOperators(
        string $selectName,
        mixed $value = null,
        array $vars = ['equal' => '=', 'greater' => '>', 'less' => '<', 'between' => '<>'],
    ): string {
        $html = '<select name="' . $selectName . '" class="comparison_selector form-control">';

        foreach ($vars as $key => $operator) {
            $html .= "<option value='" . $key . "'" . ($value && $value == $key ? ' selected' : '') . '>' . $operator . '</option>';
        }

        $html .= '</select>';

        return $html;
    }
}
