<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use MetaFramework\Accounts\Http\Requests\UpdateCompanyRequest;
use MetaFramework\Accounts\Models\Company;
use MetaFramework\Accounts\Models\CompanyData;
use MetaFramework\Services\Validation\ValidationInstance;
use MetaFramework\Support\Traits\Responses;
use Throwable;

class CompanyController
{
    use Responses;

    public function edit(): View
    {
        $company = Company::query()->with('data')->first()
            ?? new Company(Company::defaultSettingsAttributes());

        if (!$company->relationLoaded('data')) {
            $company->setRelation('data', CompanyData::query()->where('company_id', (int) $company->id)->get());
        }

        $company->hydrateLegacyTranslatables();

        return view('mfw-accounts::company.edit')->with('data', $company);
    }

    public function update(UpdateCompanyRequest $request): RedirectResponse
    {
        $validation = new ValidationInstance;
        $validation->validation($request);
        $validated = $validation->validatedData();
        $validated = is_array($validated) ? $validated : [];

        $companyId = (int) ($validated['id'] ?? 1);
        $company = Company::query()->find($companyId) ?? new Company(['id' => $companyId]);

        foreach (Company::scalarSettingsFields() as $field) {
            $company->{$field} = (string) ($validated[$field] ?? '');
        }

        try {
            $company->save();
            $this->syncLocalizedCompanyData($company, (array) ($validated['locales'] ?? []));

            $this->responseSuccess(__('ui.info_is_saved'));
            $this->redirectTo(route('mfw-accounts.company.edit'));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        return $this->sendResponse();
    }

    private function syncLocalizedCompanyData(Company $company, array $locales): void
    {
        if ($locales === []) {
            return;
        }

        $translatableFields = array_keys($company->getTranslatableProperties());
        $allowedFields = array_flip($translatableFields);
        $firstKey = array_key_first($locales);
        $updatesByLocale = [];

        if (is_string($firstKey) && in_array($firstKey, $translatableFields, true)) {
            foreach ($locales as $field => $translations) {
                if (!is_array($translations) || !isset($allowedFields[$field])) {
                    continue;
                }

                foreach ($translations as $locale => $value) {
                    $updatesByLocale[(string) $locale][$field] = $value;
                }
            }
        } else {
            foreach ($locales as $locale => $values) {
                if (!is_array($values)) {
                    continue;
                }

                $updatesByLocale[(string) $locale] = array_intersect_key($values, $allowedFields);
            }
        }

        foreach ($updatesByLocale as $locale => $values) {
            if ($values === []) {
                continue;
            }

            CompanyData::query()->updateOrInsert(
                [
                    'company_id' => (int) $company->id,
                    'lg' => $locale,
                ],
                $values,
            );
        }
    }
}
