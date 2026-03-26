<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use MetaFramework\Accounts\Support\AccountModel;

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
            'business.name' => __('mfw-accounts::ui.CompanyName'),
            'business.vat_number' => 'N° ' . __('mfw-accounts::ui.VAT'),
            'business.reg_number' => __('mfw-accounts::ui.CompanyRegNumber'),
            'business.is_seller' => __('mfw-accounts::ui.is_seller'),
            'business.seller_slug' => __('mfw-accounts::ui.SellerSlug'),
        ];
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
        $isCompany = $this->isCompanyContext();
        $businessName = $this->input('business.name');

        $rules['business'] = [$isCompany ? 'required' : 'nullable', 'array'];
        $this->applyTranslatableRule($rules, $businessName, 'business.name', $isCompany, 255);
        $rules['business.vat_number'] = ['nullable', 'string', 'max:255'];
        $rules['business.reg_number'] = ['nullable', 'string', 'max:255'];
        $rules['business.is_seller'] = [$isCompany ? 'nullable' : 'prohibited', 'boolean'];
        $rules['business.seller_slug'] = [
            $isCompany && $this->boolean('business.is_seller') ? 'required' : 'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('mfw_accounts_account_business', 'seller_slug')->ignore((int) $this->input('object_id'), 'user_id'),
        ];
    }

    private function isCompanyContext(): bool
    {
        if ($this->string('action')->toString() === 'update_client_company') {
            return true;
        }

        if ($this->boolean('is_company') || $this->has('business')) {
            return true;
        }

        $accountId = (int) $this->input('object_id');
        if ($accountId < 1) {
            return false;
        }

        $accountClass = AccountModel::className();
        $account = $accountClass::query()->with('business')->find($accountId);

        return (bool) ($account?->business);
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
