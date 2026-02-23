@php
    use MetaFramework\Accessors\Prices;
    use MetaFramework\Accounts\Accessors\AccountAccessor;
    use MetaFramework\Accounts\Accessors\InvoiceAccessor;
@endphp
@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ Str::ucfirst(trans_choice('mfw-accounts::ui.invoice', $total)) }}
    </h4>

    <div class="d-flex justify-content-end mb-2">
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                {{ __('mfw-accounts::ui.export_to') }}
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item invoice-export" href="#" data-export-format="json">JSON</a>
                <a class="dropdown-item invoice-export" href="#" data-export-format="xlsx">Excel</a>
            </div>
        </div>
    </div>
@endsection

@push('css')
    {!! csscrush_tag(public_path('vendor/mfw-accounts/css/invoices.css')) !!}
@endpush

@section('content')
    <x-mfw-support::response-messages />
    <x-mfw-support::validation-banner />

    @include('mfw-accounts::invoices.filter')
    <table class="table-striped table-bordered nowrap table-hover table">
        <thead>
            <tr>
                <td colspan="5" class="caption">
                    {{ number_format($total, 0, ' ', ' ') . ' ' . trans_choice('mfw-accounts::ui.account_document', $total) }}
                </td>
                <th class="text-nowrap text-end">
                    {{ Prices::readableFormat(price: $total_amount_eur ?? 0, currency: '', stripZeros: true) }}
                </th>
                <th class="text-nowrap text-end">
                    {{ Prices::readableFormat(price: $summary_totals['expenses_eur'] ?? 0, currency: '', stripZeros: true) }}
                </th>
                <th class="text-nowrap text-end">
                    {{ Prices::readableFormat(price: $summary_totals['payable_vat_eur'] ?? 0, currency: '', stripZeros: true) }}
                </th>
                <th class="text-nowrap text-end">
                    {{ Prices::readableFormat(price: $summary_totals['net_gain_eur'] ?? 0, currency: '', stripZeros: true) }}
                </th>
                <th class="text-nowrap text-end">
                    @php
                        $netGainPercent = $summary_totals['net_gain_percent'] ?? null;
                    @endphp
                    @if ($netGainPercent !== null && $netGainPercent != 0.0)
                        {{ number_format($netGainPercent, 2) }}%
                    @endif
                </th>
                <td colspan="8" class="caption"></td>
            </tr>
            <tr>
                <th>{{ trans_choice('mfw-accounts::ui.type', 1) }}</th>
                <th>N°</th>
                <th>{{ __('mfw-accounts::ui.Date') }}</th>
                <th>{{ trans_choice('mfw-accounts::ui.Client', 1) }}</th>
                <th>{{ __('mfw-accounts::ui.InvoiceTitle') }}</th>
                <th>{{ __('mfw-accounts::ui.Amount') }}</th>
                <th>{{ __('mfw-accounts::ui.expenses.amount') }}</th>
                <th>{{ __('mfw-accounts::ui.expenses.payable_vat') }}</th>
                <th>{{ __('mfw-accounts::ui.expenses.net_profit') }}</th>
                <th>%</th>
                <th>{{ trans_choice('mfw-accounts::ui.Currency', 1) }}</th>
                <th>Protocol</th>
                <th>{{ __('mfw-accounts::ui.InvoicePaymentState') }}</th>
                <th>{{ __('mfw-accounts::ui.operator') }}</th>
                <th>{{ __('mfw-accounts::ui.original') }}</th>
                <th>{{ __('mfw-accounts::ui.original_sent_at') }}</th>
                <th>{{ __('mfw-accounts::ui.Duplicata') }}</th>
                <th>{{ __('mfw-accounts::ui.duplicata_sent_at') }}</th>
            </tr>
        </thead>


        @foreach ($invoices as $item)
            @php
                $accessor = new InvoiceAccessor($item);
                $reportingCurrencyLabel = $accessor->reportingCurrencyLabel();
                $expenses = $accessor->expenses();
                $expenseAssociationId = $expense_associations[$item->id] ?? null;
                $expenseAssociationParentId = $expense_association_parents[$item->id] ?? null;
                $expenseAssociationProtocol = $expense_association_protocols[$item->id] ?? null;
                $totalAmount = $item->amount + $item->vat;
                $netGain = $item->net_gain ?? 0.0;
                $netGainEur = \MetaFramework\Accounts\Models\Invoice::convertAmountToReporting(
                    $netGain,
                    (int) $item->currency,
                );
                $payableVat = $netGain > 0.0 ? $netGain * 0.2 : 0.0;
                $payableVatEur = $netGain > 0.0 ? $netGainEur * 0.2 : 0.0;
                $associatedInvoices = $item->expenseAssociatedInvoices
                    ->map(
                        fn($invoice) => [
                            'id' => $invoice->id,
                            'document_id' => $invoice->document_id,
                        ],
                    )
                    ->filter(fn($invoice) => !empty($invoice['document_id']) && !empty($invoice['id']))
                    ->values();
            @endphp

            <tr>
                <td>{{ $item->docType?->admin_name ?: $item->docType?->name }}</td>
                <td>{{ $item->document_id }}</td>
                <td>{{ $item->invoice_date }}</td>
                <td>
                    @if ($item->client)
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('mfw-accounts.clients.dashboard', $item->account_id) }}">
                                {{ new AccountAccessor($item->client)->shortName() }}
                            </a>
                            {!! $item->client->isCompany()
                                ? '<span class="label-company">' . __('mfw-accounts::ui.Company') . '</span>'
                                : null !!}
                        </div>
                    @else
                        <span class="text-muted">{{ __('mfw-accounts::ui.deleted_client') }}</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('mfw-accounts.invoices.edit', $item) }}">
                        {{ $item->title ?: __('mfw-accounts::ui.untitled') }}
                    </a>
                </td>
                <td class="text-right">
                    <span
                        class="d-block">{{ Prices::readableFormat(price: $totalAmount, currency: '', stripZeros: true) }}</span>
                    @if ($accessor->usesReportingConversion() && $totalAmount != 0.0)
                        <span
                            class="d-block text-muted">{{ Prices::readableFormat(price: $accessor->totalInReportingCurrency(), currency: '', stripZeros: true) }}</span>
                    @endif
                </td>
                <td class="{{ !$item->net_gain ? 'bg-warning' : '' }} text-right">
                    @if (!$expenseAssociationId)
                        @if ($expenses != 0.0)
                            <span
                                class="d-block">{{ Prices::readableFormat(price: $expenses, currency: '', stripZeros: true) }}</span>
                            @if ($accessor->usesReportingConversion())
                                <span
                                    class="d-block text-muted">{{ Prices::readableFormat(price: $accessor->expensesInReportingCurrency(), currency: '', stripZeros: true) }}</span>
                            @endif
                        @endif
                    @endif
                </td>
                <td class="text-right">
                    @if (!$expenseAssociationId)
                        @if ($item->net_gain)
                            <span
                                class="d-block">{{ Prices::readableFormat(price: $payableVat, currency: '', stripZeros: true) }}</span>
                            @if ($accessor->usesReportingConversion())
                                <span
                                    class="d-block text-muted">{{ Prices::readableFormat(price: $payableVatEur, currency: '', stripZeros: true) }}</span>
                            @endif
                        @endif
                    @endif
                </td>
                <td class="text-right">
                    @if (!$expenseAssociationId)
                        @if ($netGain != 0.0)
                            <span
                                class="d-block">{{ Prices::readableFormat(price: $netGain, currency: '', stripZeros: true) }}</span>
                            @if ($accessor->usesReportingConversion())
                                <span
                                    class="d-block text-muted">{{ Prices::readableFormat(price: $netGainEur, currency: '', stripZeros: true) }}</span>
                            @endif
                        @endif
                    @endif
                </td>
                <td class="text-right">
                    @if (!$expenseAssociationId && $item->net_gain_percent != 0.0 && $item->net_gain_percent !== null)
                        {{ number_format($item->net_gain_percent, 2) }}%
                    @endif
                </td>
                <td
                    class="{{ $expenseAssociationId ? 'invoice-assoc-corner invoice-assoc-child' : '' }} {{ $associatedInvoices->isNotEmpty() ? 'invoice-assoc-corner invoice-assoc-parent' : '' }}">
                    @if ($expenseAssociationId)
                        <a class="d-block text-muted invoice-assoc-link"
                            href="{{ route('mfw-accounts.invoices.edit', $expenseAssociationParentId) }}" target="_blank">
                            #{{ $expenseAssociationId }}
                        </a>
                    @elseif ($associatedInvoices->isNotEmpty())
                        <span class="d-block text-muted">
                            @foreach ($associatedInvoices as $assocInvoice)
                                <a class="invoice-assoc-link"
                                    href="{{ route('mfw-accounts.invoices.edit', $assocInvoice['id']) }}" target="_blank">
                                    #{{ $assocInvoice['document_id'] }}
                                </a>
                                @if (!$loop->last)
                                    ,
                                @endif
                            @endforeach
                        </span>
                    @elseif ($totalAmount != 0.0)
                        <span class="d-block">{{ $item->currencyType?->code }}</span>
                        @if ($accessor->usesReportingConversion())
                            <span class="d-block text-muted">{{ $reportingCurrencyLabel }}</span>
                        @endif
                    @endif
                </td>
                <td>
                    @if ($expenseAssociationProtocol)
                        {{ $expenseAssociationProtocol }}
                    @else
                        {{ $item->expense_protocol_ref }}
                    @endif
                </td>
                @php
                    $isPaid = !is_null($item->paid) || !is_null($item->date_paid);
                @endphp
                <td class="{{ $totalAmount == 0.0 ? '' : ($isPaid ? 'bg-success' : 'bg-danger') }}">
                    @if ($totalAmount != 0.0)
                        {{ $isPaid ? (!is_null($item->date_paid) ? $item->date_paid : __('mfw-accounts::ui.paid')) : __('mfw-accounts::ui.unpaid') }}
                    @endif
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
                                    <img src="{{ asset('vendor/mfw/flags/' . $d->pdf_locale . '.svg') }}" alt="" />
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
    <div class="text-center">
        {{ $invoices->appends(request()->input())->links() }}
    </div>
@endsection

@push('callbacks')
    <script src="{{ asset('vendor/mfw-accounts/js/callbacks.js') }}"></script>
@endpush

@push('js')
    <script src="{{ asset('vendor/mfw-accounts/js/clients/finder.js') }}"></script>
    <script src="{{ asset('vendor/mfw-accounts/js/filters.js') }}"></script>
    <script src="{{ asset('vendor/mfw-accounts/js/invoice_from_modal.js') }}"></script>
    <script>
        $(function() {
            const exportLinks = $('.invoice-export');
            const filterForm = $('#invoice-filter').closest('form');

            const downloadBlob = function(blob, filename) {
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            };

            const downloadBase64 = function(content, mime, filename) {
                const byteCharacters = atob(content);
                const byteNumbers = new Array(byteCharacters.length);
                for (let i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }
                const byteArray = new Uint8Array(byteNumbers);
                downloadBlob(new Blob([byteArray], {
                    type: mime
                }), filename);
            };

            exportLinks.on('click', function(event) {
                event.preventDefault();
                const format = $(this).data('export-format');
                const formData = filterForm.length ? filterForm.serialize() : '';
                const payload = 'action=export_invoices&format=' + format + (formData ? '&' + formData :
                    '');

                setVeil($('body'));

                mfwAjax(payload, filterForm.length ? filterForm : $('body'), {
                    successHandler: function(result) {
                        if (result.error) {
                            return true;
                        }

                        if (format === 'xlsx') {
                            if (result.download && result.download.content) {
                                downloadBase64(result.download.content, result.download.mime,
                                    result.download.filename);
                            }

                            return false;
                        }

                        const data = {
                            rows: result.rows || [],
                            summary: result.summary || {},
                        };
                        const jsonBlob = new Blob([JSON.stringify(data, null, 2)], {
                            type: 'application/json',
                        });
                        const filename = 'invoices-export-' + new Date().toISOString().slice(0,
                            19).replace(/[:T]/g, '-') + '.json';
                        downloadBlob(jsonBlob, filename);

                        return false;
                    },
                }).always(function() {
                    removeVeil();
                });
            });
        });
    </script>
@endpush
