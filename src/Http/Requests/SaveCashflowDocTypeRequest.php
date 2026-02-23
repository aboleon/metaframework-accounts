<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use MetaFramework\Accounts\Enum\DocTypeIncrementationEnum;

class SaveCashflowDocTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_name' => 'nullable|array',
            'admin_name.*' => 'nullable|string',
            'name' => 'required|array',
            'name.*' => 'nullable|string',
            'default' => 'nullable|boolean',
            'numerotation' => [Rule::enum(DocTypeIncrementationEnum::class)],
        ];
    }
}
