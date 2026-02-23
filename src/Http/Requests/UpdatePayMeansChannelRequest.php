<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayMeansChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => 'nullable|integer|exists:mfw_accounts_bank_accounts,id',
            'data' => 'nullable|array',
            'data.name' => 'nullable|array',
            'data.name.*' => 'nullable|string',
        ];
    }
}
