<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'object_id' => 'required|integer|exists:users,id',
            'translations' => 'nullable|array',
            'translations.*' => 'nullable',
            'translations.*.*' => 'nullable|string',
        ];
    }
}
