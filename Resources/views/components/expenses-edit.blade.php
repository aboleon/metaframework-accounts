@php
    $availableInvoices = $expenseAccessor->getAvailableInvoicesForAssociation();
    $disabledIds = $expenseAccessor->getDisabledInvoiceIds();
    $currentlyAssociatedIds = $expenseAccessor->getCurrentlyAssociatedIds();

    $selectableInvoices = [];
    foreach ($availableInvoices as $availableInvoice) {
        if (
            !in_array($availableInvoice->id, $disabledIds) &&
            !in_array($availableInvoice->id, $currentlyAssociatedIds)
        ) {
            $clientName =
                $availableInvoice->client?->business?->name ??
                $availableInvoice->client?->first_name . ' ' . $availableInvoice->client?->last_name;
            $selectableInvoices[$availableInvoice->id] =
                'N° ' .
                $availableInvoice->document_id .
                ' - ' .
                $availableInvoice->invoice_date .
                ' - ' .
                $clientName .
                ' - ' .
                \MetaFramework\Accessors\Prices::readableFormat(
                    $availableInvoice->total_amount,
                    $availableInvoice->currencyType?->name ?? '',
                );
        }
    }

    $associatedInvoicesData = $invoice->expenseAssociatedInvoices->map(function ($inv) {
        $clientName = $inv->client?->business?->name ?? $inv->client?->first_name . ' ' . $inv->client?->last_name;
        return [
            'id' => $inv->id,
            'label' =>
                'N° ' .
                $inv->document_id .
                ' - ' .
                $inv->invoice_date .
                ' - ' .
                $clientName .
                ' - ' .
                \MetaFramework\Accessors\Prices::readableFormat($inv->total_amount, $inv->currencyType?->name ?? ''),
            'url' => route('mfw-accounts.invoices.edit', $inv),
        ];
    });

    $noExpensesSelected = (bool) ($invoice->getRawOriginal('no_expenses') ?? false);
@endphp

<div class="card h-100" id="expenses-card">
    <div class="card-header">
        <h5 class="card-title m-0">{{ __('mfw-accounts::ui.expenses.title') }}</h5>
    </div>
    <div class="card-body">
        <form id="expenses-form" data-ajax="{{ route('mfw-accounts.ajax') }}">
            @csrf
            <input type="hidden" name="action" value="update_expenses">
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

            <div class="row">
                <div class="col-md-4">
                    <x-mfw-inputable::input type="text" name="expenses" :label="__('mfw-accounts::ui.expenses.amount')" :value="$invoice->expenses ?? 0" />
                </div>
                <div class="col-md-4">
                    <x-mfw-inputable::input type="text" name="expense_protocol_ref" :label="__('mfw-accounts::ui.expenses.protocol_ref')"
                        :value="$invoice->expense_protocol_ref" />
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <x-mfw-inputable::checkbox name="no_expenses" :label="__('mfw-accounts::ui.expenses.no_expenses')" :affected="$noExpensesSelected ? 1 : 0" />
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <label class="form-label fw-bold">{{ __('mfw-accounts::ui.expenses.associated_invoices') }}</label>

                    <div class="d-flex mb-3 gap-2">
                        <div class="flex-grow-1">
                            <x-mfw-inputable::select name="expense_invoice_selector" id="expense_invoice_selector"
                                :values="$selectableInvoices" :nullable="true" />
                        </div>
                        <button type="button" class="btn btn-success text-nowrap" id="add_expense_association">
                            <i class="bi bi-plus-lg"></i>&nbsp;{{ __('mfw-accounts::ui.add') }}
                        </button>
                    </div>

                    <div id="expense_associations_container">
                        @foreach ($associatedInvoicesData as $assocInvoice)
                            <div class="d-flex align-items-center expense-association-item mb-2 gap-2"
                                data-id="{{ $assocInvoice['id'] }}">
                                <input type="hidden" name="expense_associations[]" value="{{ $assocInvoice['id'] }}">
                                <a href="{{ $assocInvoice['url'] }}"
                                    class="flex-grow-1">{{ $assocInvoice['label'] }}</a>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-expense-association">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> {{ __('mfw-accounts::ui.expenses.save') }}
                </button>
            </div>
        </form>
    </div>
</div>

@pushOnce('callbacks')
    <script>
        $(function() {
            const container = $('#expense_associations_container');
            const selector = $('#expense_invoice_selector');
            const addBtn = $('#add_expense_association');
            const form = $('#expenses-form');

            addBtn.on('click', function() {
                const selectedId = selector.val();
                const selectedText = selector.find('option:selected').text();

                if (!selectedId) {
                    return;
                }

                const itemHtml = `
                    <div class="d-flex align-items-center gap-2 mb-2 expense-association-item" data-id="${selectedId}">
                        <input type="hidden" name="expense_associations[]" value="${selectedId}">
                        <span class="flex-grow-1">${selectedText}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-expense-association">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                `;

                container.append(itemHtml);
                selector.find('option:selected').remove();
                selector.val('');
            });

            container.on('click', '.remove-expense-association', function() {
                const item = $(this).closest('.expense-association-item');
                const id = item.data('id');
                const label = item.find('span, a').text();

                selector.append(`<option value="${id}">${label}</option>`);
                item.remove();
            });

            form.on('submit', function(e) {
                e.preventDefault();
                mfwAjax(form.serialize(), form);
            });
        });

        window.expenses = window.expenses || {};
        window.expenses.update = function(result) {
            const editCol = $('#expenses-edit-col');
            const summaryCol = $('#expenses-summary-col');

            if (!editCol.length || !summaryCol.length) {
                return;
            }

            if (result && result.error) {
                return;
            }

            const hasSummary = result.has_summary === true || result.has_summary === 1 || result.has_summary === '1';

            if (hasSummary) {
                summaryCol.removeClass('d-none');
                if (result.summary) {
                    summaryCol.html(result.summary);
                }
                editCol.removeClass('col-12').addClass('col-12 col-lg-6');
                return;
            }

            summaryCol.addClass('d-none').empty();
            editCol.removeClass('col-lg-6');
            if (!editCol.hasClass('col-12')) {
                editCol.addClass('col-12');
            }
        };
        window['expenses.update'] = window.expenses.update;
    </script>
@endpushOnce
