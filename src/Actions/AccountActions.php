<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use MetaFramework\Accessors\Locale;
use MetaFramework\Accounts\Http\Requests\UpdateAccountClientAjaxRequest;
use MetaFramework\Accounts\Http\Requests\UpdateAddressTranslationsRequest;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Models\AccountAddress;
use MetaFramework\Polyglote\Traits\CyrillicContentTrait;
use MetaFramework\Polyglote\Traits\TransliterationTrait;
use MetaFramework\Services\GooglePlacesTranslator;
use MetaFramework\Services\Validation\ValidationInstance;
use MetaFramework\Support\Traits\Ajax;

class AccountActions
{
    use Ajax;
    use CyrillicContentTrait;
    use TransliterationTrait;

    private const int REDIRECT_DELAY_SECONDS = 3;

    public function findAccountByKeywords(Request $request): array
    {
        $term = trim((string) $request->input('data'));

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
                    'first_name' => $account->translation('first_name', $accountLocale),
                    'last_name'  => $account->translation('last_name', $accountLocale),
                    'email'      => $account->email,
                    'business'   => $account->business?->translation('name', $accountLocale),
                ];
            })
            ->values()
            ->all();

        $this->responseElement('accounts', $accounts);

        return $this->fetchResponse();
    }

    public function updateClientData(): self
    {
        $accountId = (int) request('object_id');

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

        try {
            $validation = new ValidationInstance;
            $validation->validation(UpdateAccountClientAjaxRequest::class);
            $validated = $validation->validatedData();
            $validated = is_array($validated) ? $validated : [];
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->responseError((string) $message);
                }
            }

            return $this;
        }

        $this->persistClient($account, $validated);
        $this->persistBusiness($account, $validated, $isNew);
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
        try {
            $validation = new ValidationInstance;
            $validation->validation(UpdateAddressTranslationsRequest::class);
            $validated = $validation->validatedData();
            $validated = is_array($validated) ? $validated : [];
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->responseError((string) $message);
                }
            }

            return $this;
        }

        $accountId = (int) ($validated['object_id'] ?? 0);
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

        $translations  = isset($validated['translations']) && is_array($validated['translations'])
            ? $validated['translations']
            : [];
        $allowedFields = $this->correctionFields();

        foreach ($translations as $field => $locales) {
            if (!in_array($field, $allowedFields, true)) {
                continue;
            }

            $address->{$field} = Locale::multilang() ? $locales : $this->singleLocaleValue($locales);
        }

        $address->save();
        $account->touch();
        $this->responseSuccess(__('ui.updateSuccess'));

        return $this;
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
        $stringValue = $this->singleLocaleValue($value);

        if ($stringValue === null || $stringValue === '') {
            return null;
        }

        if (!Locale::multilang()) {
            return $stringValue;
        }

        $payload      = [$fieldName => $stringValue];
        $translator   = new GooglePlacesTranslator;
        $translations = $translator->translations($payload, app()->getLocale(), $this->translatableLocales());

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
     * @return array<string, mixed>
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

        if ($payload === []) {
            return $translations;
        }

        if (!Locale::multilang()) {
            foreach ($payload as $field => $value) {
                $translations[$field] = $this->singleLocaleValue($value);
            }

            return $translations;
        }

        $translator = new GooglePlacesTranslator;

        return $translator->translations($payload, app()->getLocale(), $this->translatableLocales(), $translations);
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function existingTranslations(?AccountAddress $address, array $fields): array
    {
        if (!$address) {
            return [];
        }

        $translations = [];

        foreach ($fields as $field) {
            if (Locale::multilang() && method_exists($address, 'getTranslations')) {
                $translations[$field] = $address->getTranslations($field);

                continue;
            }

            $translations[$field] = $this->singleLocaleValue($address->{$field} ?? null);
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

    private function normalizeNameTranslations(mixed $value): array|string|null
    {
        if ($value === null) {
            return null;
        }

        if (!Locale::multilang()) {
            return $this->singleLocaleValue($value);
        }

        if (is_array($value)) {
            return $this->normalizeTranslationArray($value);
        }

        $stringValue = trim((string) $value);
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
        $fallbackLocales = collect([
            app()->getLocale(),
            config('mfw.translatable.fallback_locale'),
            config('app.fallback_locale'),
        ])->filter(fn ($locale) => is_string($locale) && $locale !== '')
            ->unique()
            ->values()
            ->all();

        if (!Locale::multilang()) {
            return $fallbackLocales ?: [app()->getLocale()];
        }

        $configured = Locale::locales();

        if (!is_array($configured) || $configured === []) {
            return $fallbackLocales ?: [app()->getLocale()];
        }

        return collect($configured)
            ->map(fn ($locale) => (string) $locale)
            ->filter(fn ($locale) => $locale !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveAccountLocale(?string $locale): string
    {
        $locales = $this->translatableLocales();

        if ($locale && in_array($locale, $locales, true)) {
            return $locale;
        }

        return (string) (config('mfw.translatable.fallback_locale') ?: config('app.fallback_locale'));
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string|null>|string|null
     */
    private function normalizeTranslationArray(array $values): array|string|null
    {
        if (!Locale::multilang()) {
            return $this->singleLocaleValue($values);
        }

        $locales      = $this->translatableLocales();
        $translations = [];

        foreach ($locales as $locale) {
            $translations[$locale] = isset($values[$locale]) ? trim((string) $values[$locale]) : null;
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
     * @return array<string, string>|string
     */
    private function buildTranslationsFromString(string $value): array|string
    {
        if (!Locale::multilang()) {
            return $value;
        }

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

    private function singleLocaleValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            $stringValue = trim((string) $value);

            return $stringValue !== '' ? $stringValue : null;
        }

        $preferredLocales = collect([
            request('locale'),
            app()->getLocale(),
            config('mfw.translatable.fallback_locale'),
            config('app.fallback_locale'),
        ])->filter(fn ($locale) => is_string($locale) && $locale !== '')
            ->unique()
            ->values()
            ->all();

        foreach ($preferredLocales as $locale) {
            if (!array_key_exists($locale, $value)) {
                continue;
            }

            $stringValue = trim((string) $value[$locale]);
            if ($stringValue !== '') {
                return $stringValue;
            }
        }

        foreach ($value as $candidate) {
            $stringValue = trim((string) $candidate);
            if ($stringValue !== '') {
                return $stringValue;
            }
        }

        return null;
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


