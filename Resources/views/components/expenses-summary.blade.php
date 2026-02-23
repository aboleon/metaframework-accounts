@php use MetaFramework\Accessors\Prices; @endphp
@if ($expenseAccessor->hasExpenseData())
    @php
        $usesReportingConversion = $expenseAccessor->usesReportingConversion();
        $reportingCurrencyLabel = $expenseAccessor->reportingCurrencyLabel();
    @endphp
    <table class="table-bordered mb-0 table">
        <tbody>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.amount') }}</th>
            <td>
                {{ $expenseAccessor->getExpensesFormatted() }}
                @if ($usesReportingConversion)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->getExpensesInReportingCurrency(), $reportingCurrencyLabel) }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.income') }}</th>
            <td>
                {{ $expenseAccessor->calculateIncomeFormatted() }}
                @if ($usesReportingConversion)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculateIncomeInReportingCurrency(), $reportingCurrencyLabel) }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.brute_profit') }}</th>
            <td>
                {{ $expenseAccessor->calculateBruteProfitFormatted() }}
                @if ($usesReportingConversion)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculateBruteProfitInReportingCurrency(), $reportingCurrencyLabel) }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.tax_base') }}</th>
            <td>
                {{ $expenseAccessor->calculateTaxBaseFormatted() }}
                @if ($usesReportingConversion)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculateTaxBaseInReportingCurrency(), $reportingCurrencyLabel) }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.payable_vat') }} (20%)</th>
            <td>
                {{ $expenseAccessor->calculatePayableVatFormatted() }}
                @if ($usesReportingConversion)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculatePayableVatInReportingCurrency(), $reportingCurrencyLabel) }})</span>
                @endif
            </td>
        </tr>
        <tr class="table-success">
            <th>{{ __('mfw-accounts::ui.expenses.net_profit') }}</th>
            <td>
                <strong>{{ $expenseAccessor->calculateNetProfitFormatted() }}</strong>
                @if ($usesReportingConversion)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculateNetProfitInReportingCurrency(), $reportingCurrencyLabel) }})</span>
                @endif
            </td>
        </tr>
        </tbody>
    </table>
@endif
