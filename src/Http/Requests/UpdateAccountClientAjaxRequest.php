<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountClientAjaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = $this->clientRules();

        $this->applyTranslatableRule($rules, $this->input('first_name'), 'first_name');
        $this->applyTranslatableRule($rules, $this->input('last_name'), 'last_name');
        $this->applyBusinessRules($rules);

        return $rules;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function clientRules(): array
    {
        return [
            'first_name' => ['nullable'],
            'last_name' => ['nullable'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:128'],
            'civ' => ['nullable', 'string', 'max:10'],
            'locale' => ['nullable', 'string', 'max:2'],
            'is_company' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function applyBusinessRules(array &$rules): void
    {
        $isCompany = $this->boolean('is_company');
        $businessName = $this->input('business.name');

        $rules['business'] = [$isCompany ? 'required' : 'nullable', 'array'];
        $this->applyTranslatableRule($rules, $businessName, 'business.name', $isCompany, 255);
        $rules['business.vat_number'] = ['nullable', 'string', 'max:255'];
        $rules['business.reg_number'] = ['nullable', 'string', 'max:255'];
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function applyTranslatableRule(
        array &$rules,
        mixed $value,
        string $key,
        bool $required = false,
        ?int $maxLength = null,
    ): void {
        $baseRule = $required ? 'required' : 'nullable';

        if (is_array($value)) {
            $rules[$key] = [$baseRule, 'array'];
            $itemRules = ['nullable', 'string'];
            if ($maxLength !== null) {
                $itemRules[] = "max:$maxLength";
            }
            $rules[$key . '.*'] = $itemRules;

            return;
        }

        $stringRules = [$baseRule, 'string'];
        if ($maxLength !== null) {
            $stringRules[] = "max:$maxLength";
        }
        $rules[$key] = $stringRules;
    }
}
