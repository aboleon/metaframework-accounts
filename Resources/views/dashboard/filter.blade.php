<form method="get" autocomplete="off" class="pull-right">
    <table>
        <tr id="invoice-filter" class="kvasir-filter" data-url="{!! Request::url() !!}" data-page-index="{!! request()->has('page')!!}">

            <?php
            $between_dates = (!request()->has('date_operator') or (request()->has('date_operator') && request()->date_operator == 'between'));
            ?>
            <input type="hidden" name="dashboard" value="1"/>
            <td class="inline-elements datepicker" data-expanded="300" <?=$between_dates ? 'style="width:300px;"' : '';?>>
                <i class="fa fa-remove" style="top: 12px;"></i>
                {!! \MetaFramework\Accounts\Helpers\AccountsHelper::comparisonOperators('date_operator', (request()->has('date_operator') ? request()->date_operator : null), [
                    "between"=>'<>',
                    "greater"=>'>',
                    "less"=>'<'
                    ]) !!}
                    <input type="text" name="date" value="{!! request()->filled('date') ? \MetaFramework\Accounts\Support\DateFormat::convert((string) request()->date, 'd/m/Y', 'd/m/Y') : null !!}" class="date form-control" style="padding: 2px">
                    <input type="text" name="date2" value="{!! ($between_dates && request()->filled('date2')) ? \MetaFramework\Accounts\Support\DateFormat::convert((string) request()->date2, 'd/m/Y', 'd/m/Y') : null !!}" class="{{ !$between_dates ? 'hidden':null}} date form-control" style="padding: 2px">
                </td>
                <td style="padding-left: 8px">
                    <button class="btn-primary btn btn-sm">{!! __('ui.filters.label') !!}</button>
                </td>
            </tr>
        </table>
    </form>
