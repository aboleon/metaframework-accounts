<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayMeansChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => 'required|integer|exists:mfw_accounts_pay_means,id',
        ];
    }
}
