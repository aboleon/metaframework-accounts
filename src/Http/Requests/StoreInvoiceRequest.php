<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:users,id',
            'doc_type' => 'required|integer|exists:mfw_accounts_cashflow_doc_types,id',
            'pdf_locale' => 'nullable|string',
        ];
    }
}
