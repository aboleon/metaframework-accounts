<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use MetaFramework\Traits\NumericInputNormalizer;

class UpdateInvoiceExpensesRequest extends FormRequest
{
    use NumericInputNormalizer;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'expenses' => $this->normalizeNumericValue($this->input('expenses')),
        ]);
    }

    public function rules(): array
    {
        return [
            'invoice_id' => 'required|integer|exists:mfw_accounts_invoices,id',
            'expenses' => 'nullable|numeric',
            'no_expenses' => 'nullable|boolean',
            'expense_protocol_ref' => 'nullable|string',
            'expense_associations' => 'nullable|array',
            'expense_associations.*' => 'integer|exists:mfw_accounts_invoices,id',
        ];
    }
}
