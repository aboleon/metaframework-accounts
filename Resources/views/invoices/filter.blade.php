@php
    $between_dates = request()->has('date_operator') && request()->date_operator == 'between';
    $between_amounts = request()->has('amount_operator') && request()->amount_operator == 'between';
    $selectedDocTypes = request('doc_type', []);
    $selectedDocTypes = is_array($selectedDocTypes) ? $selectedDocTypes : [$selectedDocTypes];
    $selectedDocTypes = array_map('intval', array_filter($selectedDocTypes, fn($value) => (string) $value !== ''));
@endphp

<form method="get" autocomplete="off" data-ajax="{{ route('mfw-accounts.ajax') }}">
    <div id="invoice-filter"
        class="kvasir-filter d-flex flex-md-nowrap align-items-end bg-body-tertiary mb-3 flex-wrap gap-2 rounded p-3"
        data-url="{!! Request::url() !!}" data-page-index="{!! request()->has('page') !!}">
        <div class="d-flex flex-column">
            <label class="form-label">{!! __('mfw-accounts::ui.PayDocType') !!}</label>
            <div class="dropdown">
                <button
                    class="form-control form-control-sm dropdown-toggle d-flex align-items-center justify-content-between gap-2 text-start"
                    type="button" id="doc-type-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="doc-type-label">{{ __('mfw-accounts::ui.PayDocType') }}</span>
                </button>
                <div class="dropdown-menu doc-type-menu p-2" aria-labelledby="doc-type-dropdown">
                    <div class="d-flex mb-2 gap-2">
                        <button type="button" class="btn btn-sm btn-light w-50 doc-type-select-all">
                            {{ __('ui.select_all') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-light w-50 doc-type-clear">
                            {{ __('ui.clear') }}
                        </button>
                    </div>
                    @foreach ($doc_types ?? [] as $id => $labels)
                        @php
                            $label = $labels['admin_name'] ?: $labels['name'];
                        @endphp
                        <x-mfw-inputable::checkbox name="doc_type[]" :label="$label" :value="$id"
                            :affected="in_array((int) $id, $selectedDocTypes, true)" />
                    @endforeach
                </div>
            </div>
        </div>
        <div class="d-flex flex-column" style="width: 90px;">
            <label class="form-label">{!! __('mfw-accounts::ui.billing_id') !!}</label>
            <input type="text" value="{!! request()->filled('billing_id') ? request()->billing_id : null !!}" name="billing_id"
                class="form-control form-control-sm">
        </div>
        <div class="d-flex flex-column">
            <label class="form-label">{!! __('mfw-accounts::ui.Date') !!}</label>
            <div class="d-flex align-items-center gap-2">
                {!! \MetaFramework\Accounts\Helpers\AccountsHelper::comparisonOperators(
                    'date_operator',
                    request()->has('date_operator') ? request()->date_operator : null,
                ) !!}
                <input type="date" name="date" value="{!! request('date') !!}"
                    class="form-control form-control-sm">
                <input type="date" name="date2" value="{!! request('date2') !!}"
                    class="form-control form-control-sm {{ $between_dates ? '' : 'hidden' }}">
            </div>
        </div>
        <div class="d-flex flex-column" style="min-width: 240px;">
            <label class="form-label">{!! trans_choice('mfw-accounts::ui.Client', 1) !!}</label>
            <div class="d-flex align-items-end gap-2">
                <div class="flex-grow-1">
                    <x-mfw-accounts::account-search id="offer-account-search" client-name-input-name="client_name"
                        client-input-name="account_id" :client-name="request('client_name')" :client-id="request()->filled('account_id') ? (int) request('account_id') : null" :placeholder="request('client_name') ?: __('mfw-accounts::ui.search_client')" />
                </div>
                <x-mfw-inputable::checkbox name="exclude" :label="__('mfw-accounts::ui.exclude')" :affected="request()->boolean('exclude')" />
            </div>
        </div>
        <div class="d-flex flex-column">
            <label class="form-label">{!! __('mfw-accounts::ui.Amount') !!}</label>
            <div class="d-flex align-items-center gap-2">
                {!! \MetaFramework\Accounts\Helpers\AccountsHelper::comparisonOperators(
                    'amount_operator',
                    request()->has('amount_operator') ? request()->amount_operator : null,
                ) !!}
                <input type="text" name="amount" value="{!! request()->filled('amount') ? request()->amount : null !!}"
                    class="form-control form-control-sm">
                <input type="text" name="amount2" value="{!! $between_amounts && request()->filled('amount2') ? request()->amount2 : null !!}"
                    class="form-control form-control-sm {{ $between_amounts ? '' : 'hidden' }}">
            </div>
        </div>
        <div class="d-flex flex-column" style="width: 160px;">
            <label class="form-label">{!! __('mfw-accounts::ui.protocol') !!}</label>
            <input type="text" value="{!! request()->filled('expense_protocol_ref') ? request()->expense_protocol_ref : null !!}" name="expense_protocol_ref"
                class="form-control form-control-sm">
        </div>
        <div class="d-flex flex-column" style="width: 130px;">
            <label class="form-label">{!! __('mfw-accounts::ui.InvoicePaid') !!}</label>
            <select name="paid" class="form-control form-control-sm">
                <option value="any"{!! request()->input('paid', 'any') === 'any' ? ' selected' : null !!}>
                    {{ __('ui.filters.nevermind') }}
                </option>
                <option value="unpaid"{!! request()->input('paid') === 'unpaid' ? ' selected' : null !!}>
                    {{ __('mfw-accounts::ui.unpaid') }}
                </option>
                <option value="paid"{!! request()->input('paid') === 'paid' ? ' selected' : null !!}>
                    {{ __('mfw-accounts::ui.paid') }}
                </option>
            </select>
        </div>
        <div class="d-flex flex-column" style="min-width: 190px;">
            <label class="form-label">{!! __('ui.filters.orderby') !!}</label>
            <select name="sort_by" class="form-control form-control-sm">
                <option value="">{{ __('ui.filters.nevermind') }}</option>
                <option value="invoice_id"{!! request()->input('sort_by') === 'invoice_id' ? ' selected' : null !!}>
                    {{ __('mfw-accounts::ui.billing_id') }}
                </option>
                <option value="date"{!! request()->input('sort_by') === 'date' ? ' selected' : null !!}>
                    {{ __('mfw-accounts::ui.Date') }}
                </option>
                <option value="amount"{!! request()->input('sort_by') === 'amount' ? ' selected' : null !!}>
                    {{ __('mfw-accounts::ui.Amount') }}
                </option>
                <option value="net_gain"{!! request()->input('sort_by') === 'net_gain' ? ' selected' : null !!}>
                    {{ __('mfw-accounts::ui.expenses.net_profit') }}
                </option>
                <option value="net_gain_percent"{!! request()->input('sort_by') === 'net_gain_percent' ? ' selected' : null !!}>
                    {{ __('mfw-accounts::ui.expenses.net_profit') }} %
                </option>
            </select>
        </div>
        <div class="d-flex flex-column" style="width: 110px;">
            <label class="form-label">{!! __('ui.filters.order') !!}</label>
            <select name="sort_dir" class="form-control form-control-sm">
                <option value="desc"{!! request()->input('sort_dir', 'desc') === 'desc' ? ' selected' : null !!}>
                    {{ __('ui.filters.desc') }}
                </option>
                <option value="asc"{!! request()->input('sort_dir') === 'asc' ? ' selected' : null !!}>
                    {{ __('ui.filters.asc') }}
                </option>
            </select>
        </div>
        <div class="ms-auto">
            <button class="btn-primary btn btn-sm">{!! __('ui.filters.label') !!}</button>
        </div>
    </div>
</form>

@push('js')
    <script>
        $(function() {
            let $dropdown = $('#doc-type-dropdown');
            let $container = $dropdown.closest('.dropdown');
            let $checkboxes = $container.find('input[name="doc_type[]"]');

            let updateLabel = function() {
                let count = $checkboxes.filter(':checked').length;
                let label = @json(__('mfw-accounts::ui.PayDocType'));
                $dropdown.find('.doc-type-label').text(count ? label + ' (' + count + ')' : label);
            };

            $container.find('.doc-type-select-all').on('click', function() {
                $checkboxes.prop('checked', true).trigger('change');
            });

            $container.find('.doc-type-clear').on('click', function() {
                $checkboxes.prop('checked', false).trigger('change');
            });

            $checkboxes.on('change', updateLabel);
            updateLabel();

        });
    </script>
@endpush

@pushonce('css')
    <style>
        .doc-type-menu {
            min-width: 260px;
        }

        #doc-type-dropdown {
            min-width: 190px;
        }

        #doc-type-dropdown .doc-type-label {
            display: inline-block;
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: bottom;
            white-space: nowrap;
        }
    </style>
@endpushonce


