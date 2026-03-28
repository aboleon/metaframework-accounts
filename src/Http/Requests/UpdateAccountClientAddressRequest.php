<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use MetaFramework\GooglePlaces\Validation\GoogleAddressValidation;

class UpdateAccountClientAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $googleAddressValidation = (new GoogleAddressValidation)
            ->setPrefix('mfw_google_places');

        return array_merge([
            'mfw_google_places' => ['required', 'array'],
            'mfw_google_places.company' => ['nullable', 'string'],
            'mfw_google_places.complementary' => ['nullable', 'string'],
            'manual_fix_address' => ['nullable', 'boolean'],
        ], $googleAddressValidation->rules());
    }

    public function messages(): array
    {
        $googleAddressValidation = (new GoogleAddressValidation)
            ->setPrefix('mfw_google_places');

        return array_merge($googleAddressValidation->messages(), [
            'mfw_google_places.array' => __('validation.array', ['attribute' => __('mfw-accounts::ui.Adress')]),
            'mfw_google_places.company.string' => __('validation.string', ['attribute' => __('mfw-accounts::ui.CompanyName')]),
            'mfw_google_places.complementary.string' => __('validation.string', ['attribute' => __('mfw-accounts::ui.Adress')]),
            'manual_fix_address.boolean' => __('validation.boolean', ['attribute' => __('mfw-accounts::ui.manual_address_fix')]),
        ]);
    }
}
