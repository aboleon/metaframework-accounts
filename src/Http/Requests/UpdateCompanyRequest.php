<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable|integer|min:1',
            'EIN' => 'nullable|string',
            'VAT' => 'nullable|string',
            'bilan_start' => 'nullable|string|max:5',
            'bilan_end' => 'nullable|string|max:5',
            'website' => 'nullable|string',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
            'locales' => 'nullable|array',
        ];
    }
}
