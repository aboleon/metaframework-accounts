<div class="alert alert-warning mt-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    {{ __('mfw-accounts::ui.expenses.associated_to_notice') }}
    <a href="{{ route('mfw-accounts.invoices.edit', $parentInvoice) }}" class="alert-link">
        N° {{ $parentInvoice->document_id }} - {{ $parentInvoice->invoice_date }}
    </a>
</div>
