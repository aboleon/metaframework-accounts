<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use MetaFramework\Traits\NumericInputNormalizer;

class ExportInvoicesRequest extends FormRequest
{
    use NumericInputNormalizer;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeNumericValue($this->input('amount')),
            'amount2' => $this->normalizeNumericValue($this->input('amount2')),
        ]);
    }

    public function rules(): array
    {
        $docTypeRules = is_array($this->input('doc_type'))
            ? ['nullable', 'array']
            : ['nullable', 'integer'];

        $rules = [
            'format' => 'nullable|in:json,xlsx',
            'sort_by' => 'nullable|in:invoice_id,date,amount,net_gain,net_gain_percent',
            'sort_dir' => 'nullable|string|in:asc,desc,ASC,DESC',
            'doc_type' => $docTypeRules,
            'date_operator' => 'nullable|in:equal,greater,less,between',
            'date' => 'nullable|string',
            'date2' => 'nullable|string',
            'dashboard' => 'nullable',
            'amount_operator' => 'nullable|in:equal,greater,less,between',
            'amount' => 'nullable|numeric',
            'amount2' => 'nullable|numeric',
            'billing_id' => 'nullable|integer',
            'paid' => 'nullable|in:yes,no,paid,unpaid,any',
            'expense_protocol_ref' => 'nullable|string',
            'account_id' => 'nullable|integer',
            'exclude' => 'nullable',
            'sale_id' => 'nullable|integer',
        ];

        if (is_array($this->input('doc_type'))) {
            $rules['doc_type.*'] = 'nullable|integer';
        }

        return $rules;
    }
}
