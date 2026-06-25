<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountClientInfoAjaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable'],
            'last_name' => ['nullable'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:128'],
            'civ' => ['nullable', 'string', 'max:10'],
            'locale' => ['nullable', 'string', 'max:2'],
            'is_company' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => __('mfw-accounts::ui.FirstName'),
            'last_name' => __('mfw-accounts::ui.LastName'),
            'email' => 'e-mail',
            'phone' => __('mfw-accounts::ui.phone'),
            'civ' => __('mfw-accounts::ui.CIV'),
            'locale' => __('ui.lg'),
            'is_company' => __('mfw-accounts::ui.is_company'),
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('mfw-accounts::ui.client.email_required'),
        ];
    }
}
