<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vat.rate' => 'numeric|unique:mfw_accounts_vat,rate',
            'vat.default' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'vat.rate.numeric' => __('validation.integer', ['attribute' => __('mfw-sellable.vat.label')]),
            'vat.rate.unique' => __('validation.unique', ['attribute' => __('mfw-sellable.vat.label')]),
        ];
    }
}
