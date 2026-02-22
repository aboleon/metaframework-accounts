@php use MetaFramework\Accessors\Prices; @endphp
@if ($expenseAccessor->hasExpenseData())
    @php
        $isBGN = $expenseAccessor->isBGN();
    @endphp
    <table class="table-bordered mb-0 table">
        <tbody>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.amount') }}</th>
            <td>
                {{ $expenseAccessor->getExpensesFormatted() }}
                @if ($isBGN)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->getExpensesInEur(), 'EUR') }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.income') }}</th>
            <td>
                {{ $expenseAccessor->calculateIncomeFormatted() }}
                @if ($isBGN)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculateIncomeInEur(), 'EUR') }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.brute_profit') }}</th>
            <td>
                {{ $expenseAccessor->calculateBruteProfitFormatted() }}
                @if ($isBGN)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculateBruteProfitInEur(), 'EUR') }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.tax_base') }}</th>
            <td>
                {{ $expenseAccessor->calculateTaxBaseFormatted() }}
                @if ($isBGN)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculateTaxBaseInEur(), 'EUR') }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('mfw-accounts::ui.expenses.payable_vat') }} (20%)</th>
            <td>
                {{ $expenseAccessor->calculatePayableVatFormatted() }}
                @if ($isBGN)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculatePayableVatInEur(), 'EUR') }})</span>
                @endif
            </td>
        </tr>
        <tr class="table-success">
            <th>{{ __('mfw-accounts::ui.expenses.net_profit') }}</th>
            <td>
                <strong>{{ $expenseAccessor->calculateNetProfitFormatted() }}</strong>
                @if ($isBGN)
                    <span
                        class="text-muted">({{ Prices::readableFormat($expenseAccessor->calculateNetProfitInEur(), 'EUR') }})</span>
                @endif
            </td>
        </tr>
        </tbody>
    </table>
@endif
