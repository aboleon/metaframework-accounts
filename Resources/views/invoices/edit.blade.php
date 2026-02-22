@php
    use MetaFramework\Accessors\Prices;
    use MetaFramework\Accounts\Models\Invoice;
@endphp
@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {!! Str::ucfirst(trans_choice('mfw-accounts::ui.invoice', 1)) !!}
    </h4>

    <div>
        <a class="btn btn-secondary" href="{{ route('mfw-accounts.clients.index') }}">
            <i class="bi bi-people-fill"></i> {{ __('mfw-accounts::ui.ClientList') }}
        </a>

        @if ($data)
            <a class="btn btn-dark ms-2" href="{{ route('mfw-accounts.clients.dashboard', $data['client']['id']) }}">
                <i class="bi bi-person-vcard"></i> {{ __('mfw-accounts::ui.ClientAccount') }}</a>
        @endif
    </div>
@endsection

@push('css')
    <style>
        .form label {
            display: inline-block;
            padding-bottom: 5px;
        }
    </style>
@endpush

@section('content')

    @if ($data)
        <?php
        $client = $data['client']; ?>
    @endif

    <x-mfw-support::response-messages />

    @include('mfw-accounts::account.dashboard_card')

    <div class="card mt-5">
        <div class="card-header d-flex justify-content-between">
            <h3 class="card-title m-0">{!! Str::ucfirst(trans_choice('mfw-accounts::ui.invoice', 1)) !!}</h3>
            @if ($data)
                <div class="d-flex align-items-center gap-2">

                    <img src="{{ asset('Modules/css/flags/' . $data->pdf_locale . '.png') }}" class="me-1"
                        style="max-height: 20px; border:1px solid white" alt="" />

                    <a href="{!! url('mfw-accounts/pdf/' . $data->hash) !!}" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="PDF" class="btn btn-danger d-flex align-items-center">

                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>

                    <x-mfw::simple-modal id="send_invoice" class="btn btn-violet-light"
                        title="{{ __('mfw-accounts::mailer/invoice.modal_title') }}"
                        body="{{ __('mfw-accounts::mailer/invoice.modal_question') }}"
                        confirmclass="btn-success confirm-send-invoice" confirm="{{ __('mfw-accounts::ui.Send') }}"
                        callback="bindSendInvoiceByMail" :modelid="$data->id" identifier="{{ $data->hash }}"
                        linktitle="{{ __('mfw-accounts::ui.Send') }}" text='<i class="bi bi-envelope-fill"></i>' />
                </div>
            @endif
        </div>
        <div class="card-body form" data-ajax="{{ route('mfw-accounts.ajax') }}">
            <form method="post" action="{{ route('mfw-accounts.invoices.update', $data) }}"
                data-ajax="{{ route('mfw-accounts.ajax') }}" autocomplete=off id=callForm>
                @csrf
                @method('PUT')
                <input type="hidden" name="object" value="Invoices">
                <input type="hidden" name="ajax_action" value="process">
                <input type="hidden" name="object_id" value="{{ $data->id ?? null }}" id='invoiceId' />

                <div class="row" style="padding-bottom: 10px;">
                    <div class="form-group col-md-2">
                        <label>{!! Str::ucfirst(__('mfw-accounts::ui.PayDocType')) !!}</label><br>
                        {!! \MetaFramework\Accounts\Helpers\AccountsHelper::selectDocTypes($data ? $data->doc_type : 1) !!}
                    </div>
                    <div class="form-group col-md-2">
                        @if ($data)
                            <label for="documentId">N°</label><br>
                            <input name='document_id' value="{{ $data->document_id ?? null }}" id='documentId'
                                class='form-control' />
                        @endif
                    </div>

                    <div class="form-group col-md-2">
                        <label for="callDate">{!! __('mfw-accounts::ui.Date') !!}</label><br>
                        <input name='invoice_date' value="{{ $data->invoice_date }}" id='callDate'
                            class='date form-control' style='width:100px' data-date-format="dd/mm/yyyy" />
                    </div>

                    <div class="form-group col-md-2">
                        <label>{!! trans_choice('mfw-accounts::ui.Currency', 1) !!}</label><br>
                        <select name="currency" class="form-control">
                            @foreach ($currencies as $currency)
                                <option label="{{ $currency->code }}" value="{{ $currency->id }}"
                                    data-sign="{{ $currency->sign }}" data-name="{{ $currency->name }}"
                                    {{ $data->currency == $currency->id ? ' selected' : null }}></option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group pull-left" style="margin-left:20px">
                            <label>{!! __('mfw-accounts::ui.PDF_Locale') !!}</label><br>
                            @if (config('app.locales'))
                                <select name="pdf_locale" id="pdf_locale" class="form-control">
                                    @foreach (explode(',', config('app.locales')) as $key => $value)
                                        <option value="{{ $value }}" {!! $data && $value == $data->pdf_locale ? 'selected="selected"' : null !!}>{{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                {{ config('app.locale') }}
                            @endif
                        </div>
                    </div>

                    <div class="form-group col-md-2">
                        <label>{!! trans_choice('mfw-accounts::ui.SellChannels', 1) !!}</label><br>
                        {!! MetaFramework\Accounts\Models\AccountsConfig::Form(
                            'SellChannels',
                            'sell_channel',
                            $data ? $data->sell_channel : null,
                        ) !!}
                    </div>
                </div>

                <div class="row {!! ($data && $data->doc_type != 4 or empty($data)) ? 'hidden' : null !!}" id="attachedToInvoice">

                    <div class="form-group col-md-12">
                        <label>{!! __('mfw-accounts::ui.attachTo') !!}</label><br>
                        <select name="attached_to">
                            @foreach ($client->invoices as $f)
                                <option value="{{ $f->document_id }}"{!! $data && $data->attached_to == $f->document_id ? " selected='selected'" : null !!}>
                                    N°
                                    {{ $f->document_id . ' от ' . $f->invoice_date . ' ' . $f->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="callTitle">{!! __('mfw-accounts::ui.InvoiceTitle') !!}</label><br>
                        <input name='title' value="{{ $data->title ?? null }}" id='callTitle' class='form-control' />
                    </div>
                </div>

                <table id="callContainer" class="table-condensed table-responsive table capitalize">
                    <thead>
                        <tr>
                            <th>{!! __('mfw-accounts::ui.Prestation') !!}</th>
                            <th>{!! __('mfw-accounts::ui.ShortQuantity') !!}</th>
                            <th>{!! __('mfw-accounts::ui.Price') !!}</th>
                            <th>{!! __('mfw-accounts::ui.VAT') !!}</th>
                            <th>{!! __('mfw-accounts::ui.VATRate') !!}</th>
                            <th>{!! __('mfw-accounts::ui.SubTotal') !!}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>

                        @if ($data && $data->details->isEmpty() === false)
                            @php
                                $amountTVA = [];
                                $amountHT = [];
                                $total = 0;
                                $virgoat = 0;
                            @endphp

                            @foreach ($data->details as $key => $virgo)
                                @php
                                    $vatValue = $virgo['vat'] / 100;
                                    $subtotal = $virgo['amount'] * $virgo['quantity'] + $vatValue;
                                    $total += $subtotal;
                                    $amountHT[] = $virgo['amount'];
                                    $amountTVA[] = $vatValue;
                                @endphp

                                <tr>
                                    <td class="NoLeftPadding">
                                        <textarea name='content[]' class="form-control">{!! $virgo['content'] !!}</textarea>
                                    </td>
                                    <td class="unit">
                                        <input name='quantity[]' value="{!! $virgo['quantity'] !!}" size=2
                                            class='digit center form-control' />
                                    </td>
                                    <td class="price">
                                        <input name='amount[]' value="{!! $virgo['amount'] !!}" size=8
                                            class='digit center form-control' />
                                    </td>
                                    <td class="vat">
                                        <span>{!! $vatValue !!}</span>
                                        <input type="hidden" name='vat[]' value="{!! $vatValue !!}" />
                                    </td>
                                    <td class="vat_select">
                                        <select name="vat_id[]" class="form-control">{!! \MetaFramework\Accounts\Accessors\VatAccessor::selectableOptionHtmlList($virgo['vat_id']) !!}</select>
                                    </td>
                                    <td class='subtotal text-right'>
                                        <span class="subtotal">{!! str_replace('.00', '', number_format($subtotal, 2, '.', ' ')) !!}</span>
                                        <span class="currency">{!! $data->currencyType->name !!}</span>
                                    </td>
                                    <th><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></th>
                                </tr>
                            @endforeach
                            @php
                                $amountHT = array_sum($amountHT);
                                $amountTVA = array_sum($amountTVA);
                            @endphp
                        @else
                            @if ($data)
                                <?php
                                $amountTVA = ($data->vat ?? 0) / 100;
                                $amountHT = ($data->amount ?? 0) * ($data->quantity ?? 1);
                                ?>
                            @endif

                            <tr>
                                <td class="NoLeftPadding">
                                    <textarea name='content[]' class="form-control">{!! $data->content ?? null !!}</textarea>
                                </td>
                                <td class="unit">
                                    <input name='quantity[]' value="{!! $data->quantity ?? 1 !!}" size=2
                                        class='digit center form-control' />
                                </td>
                                <td class="price">
                                    <input name='amount[]' value="{!! $data->amount ?? 0 !!}" size=8
                                        class='digit center form-control' />
                                </td>
                                <td class="vat">
                                    <span>{{ $data ? $data->vat : null }}</span>
                                    <input type="hidden" name='vat[]' value="{!! $data->vat ? $data->vat / 100 : 0 !!}" />
                                </td>
                                <td class="vat_select">
                                    <select name="vat_id[]" class="form-control">{!! \MetaFramework\Accounts\Accessors\VatAccessor::selectableOptionHtmlList($data?->vat_id) !!}</select>
                                </td>
                                <td class='subtotal text-right'>
                                    <span class="subtotal"></span>
                                    <span class="currency"></span>
                                </td>
                                <th><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></th>
                            </tr>
                        @endif
                    </tbody>
                </table>

                <div class="pull-right text-end" style="margin:0 0 10px 0;">
                    <h3>
                        <span id="totalSUM">{!! $data ? Prices::readableFormat(price: $data->amount + $data->vat, currency: '') : 0 !!}</span>
                        <span class="currency">{!! $data ? $data->currencyType->name : null !!}</span>
                    </h3>
                    @if ($accessor->isBGN())
                        <h5 id="totalSUMConverted" class="text-muted" data-rate="{{ Invoice::BGN_TO_EUR_RATE }}"
                            data-bgn-id="{{ Invoice::BGN_CURRENCY_ID }}">
                            {{ Prices::readableFormat(price: $accessor->bgnToEur(), currency: '') }} EUR
                        </h5>
                    @endif

                </div>

                <div class="clearfix"></div>

                <button id="addLine"
                    class="clearfix btn btn-success btn-xs pull-right">{!! __('mfw-accounts::ui.AddNewLine') !!}</button>

                <div class="clearfix"></div>

                <div style="margin:30px 0;">
                    @php
                        $paidAffected = 'upon_receival';
                        if (!is_null($data->date_paid) || !is_null($data->paid)) {
                            $paidAffected = 'date_paid';
                        } elseif (!is_null($data->date_before)) {
                            $paidAffected = 'date_before';
                        }
                    @endphp
                    <div class="row">
                        <div class="col-md-4">
                            <x-mfw-inputable::radio name="paid" id="paid-status" class="d-flex flex-column"
                                :values="[
                                    'upon_receival' => __('mfw-accounts::ui.ToPayOnReceipt'),
                                    'date_before' => __('mfw-accounts::ui.ToPayBefore'),
                                    'date_paid' => __('mfw-accounts::ui.paid'),
                                ]" :affected="$paidAffected" />
                            <div class="form-group paid-date-input" data-for="date_before"
                                style="{{ $paidAffected !== 'date_before' ? 'display:none' : '' }}">
                                <x-mfw-inputable::datepicker name="date_before" :value="$data->date_before" />
                                <div class="invalid-feedback" data-paid-feedback="date_before">
                                    {{ __('mfw-accounts::ui.DateBeforeRequired') }}
                                </div>
                            </div>
                            <div class="form-group paid-date-input" data-for="date_paid"
                                style="{{ $paidAffected !== 'date_paid' ? 'display:none' : '' }}">
                                <x-mfw-inputable::datepicker name="date_paid" :value="$data->date_paid" />
                                <div class="form-text text-danger d-none" data-paid-notice="date_paid">
                                    {{ __('mfw-accounts::ui.PaidDateDefaultNotice') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <x-mfw-inputable::select :label="__('mfw-accounts::ui.PaidBy')" name="pay_mean" :values="\MetaFramework\Accounts\Accessors\Selectables::payMeans()"
                                        :affected="$data->pay_mean" />
                                </div>

                                <div class="col-md-6">
                                    <label style="margin-right:10px">{!! __('mfw-accounts::ui.BankAccount') !!}</label>
                                    <select class="form-control" name="bank_account">
                                        @foreach ($bank_accounts as $bank)
                                            <option value="{{ $bank->object_id }}"{!! $data && $data->bank_account == $bank->object_id ? " selected='selected'" : null !!}>
                                                {{ $bank->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <x-mfw-inputable::textarea :label="__('mfw-accounts::ui.NotesToInvoice')" name="notes" :value="$data->notes"
                                        :params="['placeholder' => __('mfw-accounts::ui.TextOptional')]" />
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="text-center">
                        <button type="submit" name="save" value="{!! __('ui.save') !!}"
                            class="btn btn-info btn-lg ajaxable mt-3">
                            {!! __('ui.save') !!}
                        </button>
                    </div>
                </div>

                <input type="hidden" name='amountHT' value="{!! $data ? $amountHT : null !!}" />
                <input type="hidden" name='amountTVA' value="{!! $data ? $amountTVA : null !!}" />
                <input type="hidden" name='id' value="{!! $data ? $data->id : null !!}" />
                <input type="hidden" name='account_id' value="{!! isset($client) ? $client['id'] : null !!}" />
            </form>

            <div class="mfw-line-separator"></div>
            <div id="expenses-dashboard">
                <x-mfw-accounts::expenses-associated-notice :invoice="$data" />

                @php
                    $canEditExpenses = $data->canEditExpenses();
                    $showExpensesSummary = $canEditExpenses && $expenseAccessor->hasExpenseData();
                @endphp
                <div class="row g-4 mt-4{{ $canEditExpenses ? '' : ' d-none' }}" id="expenses-row">
                    <div id="expenses-edit-col" class="{{ $showExpensesSummary ? 'col-12 col-lg-6' : 'col-12' }}">
                        <x-mfw-accounts::expenses-edit :invoice="$data" :expenseAccessor="$expenseAccessor" />
                    </div>
                    <div id="expenses-summary-col"
                        class="col-12 col-lg-6{{ $showExpensesSummary ? '' : ' d-none' }}">
                        @if ($showExpensesSummary)
                            <x-mfw-accounts::expenses-summary :invoice="$data" :expenseAccessor="$expenseAccessor" />
                        @endif
                    </div>
                </div>
            </div>
            <hr />
        </div>
    </div>

@endsection


@push('js')
    <script src="{!! asset('vendor/mfw-accounts/js/callbacks.js') !!}"></script>
    <script src="{!! asset('vendor/mfw-accounts/js/clients/finder.js') !!}"></script>
    <script src="{!! asset('vendor/mfw-accounts/js/invoice.js') !!}"></script>
    <script src="{!! asset('vendor/mfw-accounts/js/invoice_from_modal.js') !!}"></script>
@endpush
