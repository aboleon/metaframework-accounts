<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Validation\Rule;
use MetaFramework\Accounts\Models\Vat as VatModel;

class UpdateVatRequest extends StoreVatRequest
{
    public function rules(): array
    {
        $vat = $this->route('vat');
        $vatId = $vat instanceof VatModel ? $vat->id : (is_numeric($vat) ? (int) $vat : null);

        return [
            'vat.rate' => [
                'numeric',
                Rule::unique('mfw_accounts_vat', 'rate')->ignore($vatId),
            ],
            'vat.default' => 'nullable',
        ];
    }
}
