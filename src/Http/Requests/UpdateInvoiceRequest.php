<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use MetaFramework\Traits\NumericInputNormalizer;

class UpdateInvoiceRequest extends FormRequest
{
    use NumericInputNormalizer;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeNumericArrayValue($this->input('amount')),
            'vat' => $this->normalizeNumericArrayValue($this->input('vat')),
            'quantity' => $this->normalizeNumericArrayValue($this->input('quantity')),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable|integer|exists:mfw_accounts_invoices,id',
            'object_id' => 'nullable|integer|exists:mfw_accounts_invoices,id',
            'account_id' => 'required|integer|exists:users,id',
            'doc_type' => 'required|integer|exists:mfw_accounts_cashflow_doc_types,id',
            'document_id' => 'nullable|integer',
            'invoice_date' => 'required|string',
            'currency' => 'required|integer',
            'sell_channel' => 'nullable',
            'title' => 'nullable|string',
            'notes' => 'nullable|string',
            'pdf_locale' => 'nullable|string',
            'bank_account' => 'nullable|integer',
            'attached_to' => 'nullable|integer',
            'sale_id' => 'nullable|integer',
            'date_paid' => 'nullable|string',
            'date_before' => 'nullable|string|required_if:paid,date_before',
            'pay_mean' => 'nullable|integer',
            'paid' => 'nullable',
            'amount' => 'array',
            'amount.*' => 'numeric',
            'vat' => 'array',
            'vat.*' => 'numeric',
            'vat_id' => 'array',
            'vat_id.*' => 'integer|nullable',
            'quantity' => 'array',
            'quantity.*' => 'required|integer|min:1',
            'content' => 'array',
            'content.*' => 'nullable|string',
            'expenses' => 'nullable|numeric',
            'expense_associations' => 'nullable|array',
            'expense_associations.*' => 'integer|exists:mfw_accounts_invoices,id',
        ];
    }
}
