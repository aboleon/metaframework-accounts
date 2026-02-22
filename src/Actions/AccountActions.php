<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MetaFramework\Accessors\Locale;
use MetaFramework\Polyglote\Traits\CyrillicContentTrait;
use MetaFramework\Polyglote\Traits\TransliterationTrait;
use MetaFramework\Services\GooglePlacesTranslator;
use MetaFramework\Support\Traits\Ajax;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Models\AccountAddress;

class AccountActions
{
    use Ajax;
    use CyrillicContentTrait;
    use TransliterationTrait;

    private const int REDIRECT_DELAY_SECONDS = 3;

    public function findAccountByKeywords(Request $request): array
    {
        $term = trim((string)$request->input('data'));

        if ($term === '' || strlen($term) < 2) {
            $this->responseElement('accounts', []);

            return $this->fetchResponse();
        }

        $locales       = $this->translatableLocales();
        $variants      = collect($locales)
            ->flatMap(fn (string $variantLocale) => $this->getSearchVariants($term, $variantLocale, true))
            ->filter(fn (string $variant) => $variant !== '')
            ->unique()
            ->values()
            ->all();

        $accounts = Account::query()
            ->select('id', 'first_name', 'last_name', 'email', 'locale')
            ->with('business')
            ->where(function ($query) use ($variants, $locales) {
                foreach ($variants as $variant) {
                    $like = '%' . $variant . '%';

                    $query->orWhere(function ($variantQuery) use ($like, $locales) {
                        foreach ($locales as $searchLocale) {
                            $firstNameExpr = Account::localizedColumn('first_name', $searchLocale);
                            $lastNameExpr  = Account::localizedColumn('last_name', $searchLocale);

                            $variantQuery
                                ->orWhereRaw($firstNameExpr . ' like ?', [$like])
                                ->orWhereRaw($lastNameExpr . ' like ?', [$like])
                                ->orWhereHas('business', function ($businessQuery) use ($like, $searchLocale) {
                                    $nameExpr = Account::localizedColumn('name', $searchLocale);
                                    $businessQuery->whereRaw($nameExpr . ' like ?', [$like]);
                                });
                        }
                    });
                }
            })
            ->orderBy('last_name')
            ->get()
            ->map(function (Account $account) {
                $accountLocale = $this->resolveAccountLocale($account->locale);

                return [
                    'id'         => $account->id,
                    'first_name' => $account->getTranslation('first_name', $accountLocale),
                    'last_name'  => $account->getTranslation('last_name', $accountLocale),
                    'email'      => $account->email,
                    'business'   => $account->business?->getTranslation('name', $accountLocale),
                ];
            })
            ->values()
            ->all();

        $this->responseElement('accounts', $accounts);

        return $this->fetchResponse();
    }

    public function updateClientData(): self
    {
        $accountId = (int)request('object_id');

        if ($accountId) {
            $account = Account::find($accountId);
            if (!$account) {
                $this->responseError(__('mfw-accounts::ui.client.not_found'));

                return $this;
            }
            $isNew = false;
        } else {
            $account = new Account;
            $isNew   = true;
        }

        $rules = $this->clientRules();
        $this->applyTranslatableRule($rules, request('first_name'), 'first_name');
        $this->applyTranslatableRule($rules, request('last_name'), 'last_name');
        $this->applyBusinessRules($rules);

        $validator = validator(request()->all(), $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->responseError($message);
            }

            return $this;
        }

        $this->persistClient($account, $validator->validated());
        $this->persistBusiness($account, $validator->validated(), $isNew);
        $this->persistAddress($account, request()->input('mfw_google_places', []));

        $this->responseSuccess(__('mfw-accounts::ui.client.is_saved'));

        if (!$accountId) {
            $this->responseElement('callback', 'redirectClientEdit');
            $this->responseElement('redirect_delay', self::REDIRECT_DELAY_SECONDS);
            $this->responseNotice(__('mfw-accounts::ui.client.redirect_notice', ['seconds' => self::REDIRECT_DELAY_SECONDS]));
        }

        $this->responseElement('client_id', $account->id);

        return $this;
    }

    public function updateAddressTranslations(): self
    {
        $accountId = (int)request('object_id');
        $account   = Account::find($accountId);

        if (!$account) {
            $this->responseError(__('mfw-accounts::ui.client.not_found'));

            return $this;
        }

        $address = $account->address()->where('billing', 1)->first();
        if (!$address) {
            $this->responseError(__('mfw-accounts::ui.address_not_found'));

            return $this;
        }

        $translations  = (array)request('translations', []);
        $allowedFields = $this->correctionFields();

        foreach ($translations as $field => $locales) {
            if (!in_array($field, $allowedFields, true)) {
                continue;
            }

            $address->{$field} = $locales;
        }

        $address->save();
        $account->touch();
        $this->responseSuccess(__('ui.updateSuccess'));

        return $this;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function clientRules(): array
    {
        return [
            'first_name' => ['nullable'],
            'last_name'  => ['nullable'],
            'email'      => ['nullable', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:128'],
            'civ'        => ['nullable', 'string', 'max:10'],
            'locale'     => ['nullable', 'string', 'max:2'],
            'is_company' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function correctionFields(): array
    {
        return [
            'route',
            'locality',
        ];
    }

    private function persistClient(Account $account, array $data): void
    {
        $isNew = !$account->exists;

        foreach (['first_name', 'last_name'] as $field) {
            if (array_key_exists($field, $data)) {
                $account->{$field} = $isNew
                    ? $this->translateNameService(request($field), $field)
                    : $this->normalizeNameTranslations(request($field));
            }
        }

        $account->email  = $data['email'] ?? $account->email;
        $account->phone  = $data['phone'] ?? $account->phone;
        $account->civ    = $data['civ'] ?? $account->civ;
        $account->locale = $data['locale'] ?? $account->locale;

        if ($isNew && empty($account->password)) {
            $account->password = Str::random(40);
        }

        $account->save();
    }

    private function persistBusiness(Account $account, array $data, bool $isNew): void
    {
        $isCompany = request()->boolean('is_company');

        if (!$isCompany) {
            if ($account->business) {
                $account->business()->delete();
            }

            return;
        }

        $business  = $data['business'] ?? [];
        $nameValue = $business['name'] ?? null;
        $payload   = [
            'name'       => $isNew
                ? $this->translateNameService($nameValue, 'name')
                : $this->normalizeNameTranslations($nameValue),
            'vat_number' => $business['vat_number'] ?? null,
            'reg_number' => $business['reg_number'] ?? null,
        ];

        if ($account->business) {
            $account->business->update($payload);

            return;
        }

        $account->business()->create($payload);
    }

    private function translateNameService(mixed $value, string $fieldName): array|string|null
    {
        if ($value === null) {
            return null;
        }

        $stringValue = is_array($value) ? ($value[app()->getLocale()] ?? reset($value)) : trim((string)$value);

        if ($stringValue === '') {
            return null;
        }

        $payload      = [$fieldName => $stringValue];
        $translator   = new GooglePlacesTranslator;
        $translations = $translator->translations($payload, app()->getLocale(), Locale::locales());

        return $translations[$fieldName] ?? $this->normalizeNameTranslations($value);
    }

    private function persistAddress(Account $account, array $geoData): void
    {
        if (empty($geoData)) {
            return;
        }

        $billingAddress = $account->address()->where('billing', 1)->first();
        $addressData    = $this->addressPayload($geoData, $billingAddress);

        if ($billingAddress) {
            $billingAddress->update($addressData);

            return;
        }

        $account->address()->create($addressData);
    }

    /**
     * @param  array<string, mixed>  $geoData
     * @return array<string, mixed>
     */
    private function addressPayload(array $geoData, ?AccountAddress $address = null): array
    {
        $translations = $this->resolveTranslations($geoData, $address);

        return [
            'street_number'               => $geoData['street_number'] ?? null,
            'route'                       => $translations['route'] ?? null,
            'locality'                    => $translations['locality'] ?? null,
            'postal_code'                 => $geoData['postal_code'] ?? null,
            'country_code'                => $geoData['country_code'] ?? null,
            'place_id'                    => $geoData['place_id'] ?? null,
            'text_address'                => $geoData['text_address'] ?? null,
            'lat'                         => $geoData['lat'] ?? null,
            'lon'                         => $geoData['lon'] ?? null,
            'company'                     => $geoData['company'] ?? null,
            'complementary'               => $geoData['complementary'] ?? null,
            'administrative_area_level_1' => $translations['administrative_area_level_1'] ?? null,
            'administrative_area_level_2' => $translations['administrative_area_level_2'] ?? null,
            'billing'                     => 1,
        ];
    }

    /**
     * @param  array<string, mixed>  $geoData
     * @return array<string, array<string, string>>
     */
    private function resolveTranslations(array $geoData, ?AccountAddress $address = null): array
    {
        $fields          = $this->translatableFields();
        $translations    = $this->existingTranslations($address, $fields);
        $newPlaceId      = $geoData['place_id'] ?? null;
        $existingPlaceId = $address?->place_id;

        if (!request()->boolean('manual_fix_address') && $newPlaceId && $newPlaceId === $existingPlaceId) {
            return $translations;
        }

        $payload = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $geoData)) {
                $payload[$field] = $geoData[$field];
            }
        }

        $translator = new GooglePlacesTranslator;

        return $translator->translations($payload, app()->getLocale(), Locale::locales(), $translations);
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, array<string, string>>
     */
    private function existingTranslations(?AccountAddress $address, array $fields): array
    {
        if (!$address) {
            return [];
        }

        $translations = [];

        foreach ($fields as $field) {
            $translations[$field] = $address->getTranslations($field);
        }

        return $translations;
    }

    /**
     * @return array<int, string>
     */
    private function translatableFields(): array
    {
        return [
            'route',
            'locality',
            'administrative_area_level_1',
            'administrative_area_level_2',
        ];
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function applyBusinessRules(array &$rules): void
    {
        $isCompany    = request()->boolean('is_company');
        $businessName = request()->input('business.name');

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
            $itemRules   = ['nullable', 'string'];
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

    private function normalizeNameTranslations(mixed $value): array|string|null
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $this->normalizeTranslationArray($value);
        }

        $stringValue = trim((string)$value);
        if ($stringValue === '') {
            return null;
        }

        return $this->buildTranslationsFromString($stringValue);
    }

    /**
     * @return array<int, string>
     */
    private function translatableLocales(): array
    {
        return config('mfw.translatable.locales', ['fr', 'bg', 'en']);
    }

    private function resolveAccountLocale(?string $locale): string
    {
        $locales = $this->translatableLocales();

        if ($locale && in_array($locale, $locales, true)) {
            return $locale;
        }

        return config('app.fallback_locale');
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string|null>
     */
    private function normalizeTranslationArray(array $values): array
    {
        $locales      = $this->translatableLocales();
        $translations = [];

        foreach ($locales as $locale) {
            $translations[$locale] = isset($values[$locale]) ? trim((string)$values[$locale]) : null;
        }

        $latinSource    = $translations['fr'] ?? $translations['en'] ?? null;
        $cyrillicSource = $translations['bg'] ?? null;

        if ($latinSource === null && $cyrillicSource !== null) {
            $latinSource = $this->transliterate('Cyrillic-Latin', $cyrillicSource);
        }

        if ($cyrillicSource === null && $latinSource !== null) {
            $cyrillicSource = $this->transliterate('Latin-Cyrillic', $latinSource);
        }

        foreach ($locales as $locale) {
            if ($translations[$locale] !== null && $translations[$locale] !== '') {
                continue;
            }

            $translations[$locale] = $locale === 'bg' ? $cyrillicSource : $latinSource;
        }

        return $translations;
    }

    /**
     * @return array<string, string>
     */
    private function buildTranslationsFromString(string $value): array
    {
        $latin    = $value;
        $cyrillic = $value;

        if ($this->hasCyrillic($value)) {
            $latin = $this->transliterate('Cyrillic-Latin', $value);
        } else {
            $cyrillic = $this->transliterate('Latin-Cyrillic', $value);
        }

        $locales      = $this->translatableLocales();
        $translations = [];

        foreach ($locales as $locale) {
            $translations[$locale] = $locale === 'bg' ? $cyrillic : $latin;
        }

        return $translations;
    }

    private function transliterate(string $direction, string $value): string
    {
        if (!extension_loaded('intl') || !function_exists('transliterator_transliterate')) {
            return $value;
        }

        $result = transliterator_transliterate($direction, $value);

        return $result === false ? $value : $result;
    }
}
