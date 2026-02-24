@php use MetaFramework\Accessors\Prices; @endphp
@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {!! __('mfw-accounts::ui.ClientAccount') !!}
    </h4>

    <div>
        <a class="btn btn-secondary" href="{{ route('mfw-accounts.clients.index') }}">
            <i class="bi bi-people-fill"></i> {{ __('mfw-accounts::ui.ClientList') }}
        </a>
    </div>
@endsection

@section('content')
    <x-mfw-support::response-messages />
    <x-mfw-support::validation-banner />

    @include('mfw-accounts::account.dashboard_card')

    <table class="table-striped table-bordered nowrap table-hover table">
        <caption>
            @if (request()->has('sale_id'))
                {{ __('mfw-accounts::ui.limited_view') . ' - ' }}
                <a
                    href="{{ route('mfw-accounts.clients.dashboard', $client) }}">{{ __('mfw-accounts::ui.ClientAccount') }}</a>
            @endif
        </caption>
        <thead>
            <tr>
                <td colspan="13" class="caption">
                    {{ number_format($total, 0, ' ', ' ') . ' ' . trans_choice('mfw-accounts::ui.account_document', $total) }}
                </td>
            </tr>
            <tr>
                <th>{{ trans_choice('mfw-accounts::ui.type', 1) }}</th>
                <th>N°</th>
                <th>{{ __('mfw-accounts::ui.Date') }}</th>
                <th>{{ __('mfw-accounts::ui.InvoiceTitle') }}</th>
                <th>{{ __('mfw-accounts::ui.Amount') }}</th>
                <th>{{ trans_choice('mfw-accounts::ui.Currency', 1) }}</th>
                <th>{{ __('mfw-accounts::ui.VAT') }}</th>
                <th>{{ __('mfw-accounts::ui.InvoicePaymentState') }}</th>
                <th>{{ __('mfw-accounts::ui.operator') }}</th>
                <th>{{ __('mfw-accounts::ui.original') }}</th>
                <th>{{ __('mfw-accounts::ui.original_sent_at') }}</th>
                <th>{{ __('mfw-accounts::ui.Duplicata') }}</th>
                <th>{{ __('mfw-accounts::ui.duplicata_sent_at') }}</th>
            </tr>
        </thead>

        @foreach ($data as $item)
            <tr>
                <td>{{ $item->docType?->name }}</td>
                <td>{{ $item->document_id }}</td>
                <td>{{ $item->invoice_date }}</td>
                <td>
                    <a href="{{ route('mfw-accounts.invoices.edit', $item) }}">
                        {{ $item->title ?: __('mfw-accounts::ui.untitled') }}
                    </a>
                </td>
                <td class="text-right">{{ Prices::readableFormat(price: $item->amount, currency: '', stripZeros: true) }}
                </td>
                <td>{{ $item->currencyType?->code }}</td>
                <td>{{ $item->vat }}</td>
                <td class="{{ !is_null($item->date_paid) ? 'bg-success' : null }}">
                    {{ is_null($item->paid) ? __('mfw-accounts::ui.unpaid') : (!is_null($item->date_paid) ? $item->date_paid : __('mfw-accounts::ui.paid')) }}
                </td>
                <td>{{ $item->operator }}</td>
                <td>
                    <ul class="mfw-actions flex-nowrap">
                        <x-mfw::edit-link :route="route('mfw-accounts.invoices.edit', $item)" />
                        <li>
                            <a href="{{ route('mfw-accounts.pdf', $item->hash) }}" target="_blank" class="btn btn-danger"
                                title="PDF" data-bs-toggle="tooltip">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        </li>
                        <li>
                            <x-mfw::simple-modal id="send_invoice" class="btn btn-violet-light"
                                title="{{ __('mfw-accounts::mailer/invoice.modal_title') }}"
                                body="{{ __('mfw-accounts::mailer/invoice.modal_question') }}"
                                confirmclass="btn-success confirm-send-invoice" confirm="{{ __('mfw-accounts::ui.Send') }}"
                                callback="bindSendInvoiceByMail" :modelid="$item->id" identifier="{{ $item->hash }}"
                                linktitle="{{ __('mfw-accounts::ui.Send') }}" text='<i class="bi bi-envelope-fill"></i>' />
                        </li>
                    </ul>
                </td>
                <td>{{ $item->sent_at?->format('d/m/Y H:i') }}</td>
                <td class="text-end">
                    @forelse($item->duplicatas as $d)
                        <ul class="mfw-actions flex-nowrap">
                            <x-mfw::edit-link :route="route('mfw-accounts.invoices.edit', $d)" />
                            <li>
                                <a href="{{ route('mfw-accounts.pdf', $d->hash) }}" target="_blank"
                                    class="label label-default flag" title="PDF" data-bs-toggle="tooltip">
                                    <img src="{{ asset('vendor/mfw/flags/4x3/' . $d->pdf_locale . '.svg') }}" alt="" />
                                </a>
                            </li>
                            <li>
                                <x-mfw::simple-modal id="send_invoice" class="btn btn-violet-light"
                                    title="{{ __('mfw-accounts::mailer/invoice.modal_title') }}"
                                    body="{{ __('mfw-accounts::mailer/invoice.modal_question') }}"
                                    confirmclass="btn-success confirm-send-invoice"
                                    confirm="{{ __('mfw-accounts::ui.Send') }}" callback="bindSendInvoiceByMail"
                                    :modelid="$d->id" identifier="{{ $d->hash }}"
                                    linktitle="{{ __('mfw-accounts::ui.Send') }}"
                                    text='<i class="bi bi-envelope-fill"></i>' />
                            </li>
                        </ul>
                    @empty
                        <form method="post" action="{{ route('mfw-accounts.invoices.duplicate', $item) }}">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-info text-nowrap"
                                title="{{ __('mfw-accounts::ui.create') }}" data-bs-toggle="tooltip">
                                <i class="bi bi-files"></i> {{ __('mfw-accounts::ui.create') }}
                            </button>
                        </form>
                    @endforelse
                </td>
                <td>
                    @foreach ($item->duplicatas as $d)
                        <div>{{ $d->sent_at?->format('d/m/Y H:i') }}</div>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </table>

    {{ $data->links() }}
@endsection

@push('callbacks')
    <script src="{{ asset('vendor/mfw-accounts/js/callbacks.js') }}"></script>
@endpush

@push('js')
    @include('mfw-accounts::clients.scripts')
    <script src="{{ asset('vendor/mfw-accounts/js/invoice_from_modal.js') }}"></script>
@endpush
