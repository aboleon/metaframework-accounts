<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveAccountAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:128'],
            'civ' => ['required', 'string', 'max:10'],
            'locale' => ['required', 'string', 'max:2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'civ' => __('mfw-accounts::ui.CIV'),
            'locale' => __('ui.lg'),
            'first_name' => __('mfw-accounts::ui.FirstName'),
            'last_name' => __('mfw-accounts::ui.LastName'),
            'email' => 'e-mail',
            'phone' => __('mfw-accounts::ui.phone'),
        ];
    }
}
