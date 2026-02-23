<table class="table">
    <tr id="invoice-filter" class="kvasir-filter" data-url="{!! Request::url() !!}"
        data-page-index="{!! request()->has('page')!!}">
        @php
            $client_type = request()->has('date_operator') ? request()->date_operator : null;
        @endphp
        <td>
            <form method="get" autocomplete="off">
                <table class="table">
                    <thead>
                    <tr>
                        <td width="170">
                            {!! __('mfw-accounts::ui.client.type') !!}
                        </td>
                        <td>
                            {!! __('mfw-accounts::ui.client.created') !!}
                        </td>
                        <td>
                            {!! __('mfw-accounts::ui.client.noms') !!}
                        </td>
                        <td width="170">
                            {!! __('ui.filters.orderby') !!}
                        </td>
                        <td width="170">
                            {!! __('ui.filters.order') !!}
                        </td>
                        <td width="100">
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>
                            <select class="form-control" name="client_type">
                                <option value="0">{!! __('ui.filters.all') !!}</option>
                                <option
                                    value="1"{!! $client_type == 1 ? ' selected' : null !!}>{!! __('mfw-accounts::ui.particulier') !!}</option>
                                <option
                                    value="2"{!! $client_type == 2 ? ' selected' : null !!}>{!! __('mfw-accounts::ui.Company') !!}</option>
                            </select>
                        </td>
                        @php
                            $between_dates = (request()->has('date_operator') && request()->date_operator == 'between');
                            $between_amounts = (request()->has('amount_operator') && request()->amount_operator == 'between');
                        @endphp
                        <td>

                            <div class="d-flex justify-content-between align-items-center">
                                {!! \MetaFramework\Accounts\Helpers\AccountsHelper::comparisonOperators('date_operator', request()->has('date_operator') ? request()->date_operator : null) !!}
                                <x-mfw-inputable::datepicker name="date" :value="request('date')" class="mx-2"/>
                                <x-mfw-inputable::datepicker name="date2" :value="request('date2')"
                                                             :class="'me-2 '. ($between_dates ? '' : 'hidden')"/>

                                <i class="fa fa-remove text-danger pointer"></i>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex justify-content-between align-items-center">
                                <x-mfw-accounts::account-search
                                    id="offer-account-search"
                                    client-name-input-name="client_name"
                                    client-input-name="account_id"
                                    :placeholder="request('client_name') ?: __('mfw-accounts::ui.search_client')"
                                />

                                <i class="fa fa-remove text-danger pointer"></i>
                            </div>
                        </td>
                        <td>
                            <select class="form-control" name="sort_by">
                                @php($sortBy = request('sort_by', 'date_created'))
                                <option value="name"{!! $sortBy === 'name' ? ' selected' : null !!}>
                                    {!! __('mfw-accounts::ui.Name') !!}
                                </option>
                                <option value="invoice_count"{!! $sortBy === 'invoice_count' ? ' selected' : null !!}>
                                    {!! trans_choice('mfw-accounts::ui.invoice', 2) !!}
                                </option>
                                <option value="date_created"{!! $sortBy === 'date_created' ? ' selected' : null !!}>
                                    {!! __('mfw-accounts::ui.client.created') !!}
                                </option>
                            </select>
                        </td>
                        <td>
                            @php($sortOrder = request('sort_order', 'desc'))
                            <select class="form-control" name="sort_order">
                                <option value="asc"{!! $sortOrder === 'asc' ? ' selected' : null !!}>
                                    {!! __('ui.filters.asc') !!}
                                </option>
                                <option value="desc"{!! $sortOrder === 'desc' ? ' selected' : null !!}>
                                    {!! __('ui.filters.desc') !!}
                                </option>
                            </select>
                        </td>
                        <td>
                            <button class="btn-secondary btn">{!! __('ui.filters.label') !!}</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </td>
    </tr>


    @push('callbacks')
        <script>

        </script>
    @endpush
    @push('js')
        <script src="{!! asset('vendor/mfw-accounts/js/clients/finder.js') !!}"></script>
        <script src="{!! asset('vendor/mfw-accounts/js/filters.js') !!}"></script>
@endpush


