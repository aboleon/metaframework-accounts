<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use MetaFramework\Accessors\Locale;
use MetaFramework\Accounts\Enum\UserType;
use MetaFramework\Accounts\Http\Requests\UpdateAccountClientAddressRequest;
use MetaFramework\Accounts\Http\Requests\UpdateAccountClientAjaxRequest;
use MetaFramework\Accounts\Http\Requests\UpdateAccountClientInfoAjaxRequest;
use MetaFramework\Accounts\Http\Requests\UpdateAddressTranslationsRequest;
use MetaFramework\Accounts\Mailer\AccountWelcome;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Models\AccountAddress;
use MetaFramework\Accounts\Models\AccountAgent;
use MetaFramework\Accounts\Services\SellerConfigSkeletonWriter;
use MetaFramework\Accounts\Support\AccountModel;
use MetaFramework\Accounts\Support\AccountWelcomePasswordStore;
use MetaFramework\Accounts\Validators\AccountMailValidator;
use MetaFramework\Mailer\Http\Controllers\MailController;
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

    /**
     * @var array<int, string>
     */
    private const array ADDRESS_PAYLOAD_SIGNAL_FIELDS = [
        'text_address',
        'place_id',
        'street_number',
        'route',
        'locality',
        'postal_code',
        'country_code',
        'administrative_area_level_1',
        'administrative_area_level_2',
        'company',
        'complementary',
    ];

    public function findAccountByKeywords(Request $request): array
    {
        $term = trim((string) $request->input('data'));
        $accountTypes = $this->resolveAccountTypes($request->input('account_type'));
        $isCompanySearch = $this->isCompanyOnlySearch($accountTypes);

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

        $accountClass = AccountModel::className();

        $accounts = $accountClass::query()
            ->select('id', 'first_name', 'last_name', 'email', 'locale')
            ->whereNull('company_id')
            ->when($accountTypes !== [], fn ($query) => $query->whereIn('type', $accountTypes))
            ->with('business')
            ->where(function ($query) use ($variants, $locales) {
                foreach ($variants as $variant) {
                    $like = '%' . $variant . '%';

                    $query->orWhere(function ($variantQuery) use ($like, $locales) {
                        foreach ($locales as $searchLocale) {
                            $firstNameExpr = AccountModel::localizedColumn('first_name', $searchLocale);
                            $lastNameExpr  = AccountModel::localizedColumn('last_name', $searchLocale);

                            $variantQuery
                                ->orWhereRaw($firstNameExpr . ' like ?', [$like])
                                ->orWhereRaw($lastNameExpr . ' like ?', [$like])
                                ->orWhereHas('business', function ($businessQuery) use ($like, $searchLocale) {
                                    $nameExpr = AccountModel::localizedColumn('name', $searchLocale);
                                    $businessQuery->whereRaw($nameExpr . ' like ?', [$like]);
                                });
                        }
                    });
                }
            })
            ->orderBy('last_name')
            ->get()
            ->map(function (Account $account) use ($isCompanySearch) {
                $accountLocale = $this->resolveAccountLocale($account->locale);
                $businessName = $account->business?->translation('name', $accountLocale);
                $firstName = $account->translation('first_name', $accountLocale);
                $lastName = $account->translation('last_name', $accountLocale);
                $displayName = $isCompanySearch
                    ? $businessName
                    : trim(collect([$firstName, $lastName])->filter()->implode(' '));

                return [
                    'id'           => $account->id,
                    'first_name'   => $isCompanySearch ? null : $firstName,
                    'last_name'    => $isCompanySearch ? null : $lastName,
                    'email'        => $account->email,
                    'business'     => $businessName,
                    'display_name' => $displayName ?: $businessName ?: trim(collect([$firstName, $lastName])->filter()->implode(' ')),
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
            $accountClass = AccountModel::className();
            $account = $accountClass::query()->find($accountId);
            if (!$account) {
                $this->responseError(__('mfw-accounts::ui.client.not_found'));

                return $this;
            }
            $isNew = false;
        } else {
            $accountClass = AccountModel::className();
            $account = new $accountClass;
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

        if (!request()->boolean('is_company') && $account->agents()->exists()) {
            $this->responseError(__('mfw-accounts::ui.company_requires_agents_cleanup'));

            return $this;
        }

        $this->persistClient($account, $validated);
        $this->persistBusiness($account, $validated, $isNew);
        $this->storeSubmittedClientAddress($account);
        if ($this->hasErrors()) {
            return $this;
        }

        $this->responseSuccess(__('mfw-accounts::ui.client.is_saved'));

        if (!$accountId) {
            $this->responseElement('callback', 'redirectClientEdit');
            $this->responseElement('redirect_delay', self::REDIRECT_DELAY_SECONDS);
            $this->responseNotice(__('mfw-accounts::ui.client.redirect_notice', ['seconds' => self::REDIRECT_DELAY_SECONDS]));
        }

        $this->responseElement('client_id', $account->id);

        return $this;
    }

    public function updateClientInfoData(): self
    {
        [$account, $isNew] = $this->resolveAccountForUpdate();
        if (!$account) {
            return $this;
        }

        try {
            $validation = new ValidationInstance;
            $validation->validation(UpdateAccountClientInfoAjaxRequest::class);
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

        if (!request()->boolean('is_company') && $account->agents()->exists()) {
            $this->responseError(__('mfw-accounts::ui.company_requires_agents_cleanup'));

            return $this;
        }

        $this->persistClient($account, $validated);
        $this->persistCompanyState($account, request()->boolean('is_company'));
        $this->storeSubmittedClientAddress($account);
        if ($this->hasErrors()) {
            return $this;
        }

        $this->responseSuccess(__('mfw-accounts::ui.client.is_saved'));

        if ($isNew) {
            $this->responseElement('callback', 'redirectClientEdit');
            $this->responseElement('redirect_delay', self::REDIRECT_DELAY_SECONDS);
            $this->responseNotice(__('mfw-accounts::ui.client.redirect_notice', ['seconds' => self::REDIRECT_DELAY_SECONDS]));
        }

        $this->responseElement('client_id', $account->id);

        return $this;
    }

    public function updateClientAddressData(): self
    {
        [$account] = $this->resolveAccountForUpdate(requireExisting: true);
        if (!$account) {
            return $this;
        }

        try {
            $validation = new ValidationInstance;
            $validation->validation(UpdateAccountClientAddressRequest::class);
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

        $this->persistAddress($account, (array) ($validated['mfw_google_places'] ?? []));
        $this->responseSuccess(__('mfw-accounts::ui.client.is_saved'));
        $this->responseElement('client_id', $account->id);

        return $this;
    }

    public function updateClientCompanyData(): self
    {
        [$account] = $this->resolveAccountForUpdate(requireExisting: true);
        if (!$account) {
            return $this;
        }

        $attributes = app(UpdateAccountClientAjaxRequest::class)->attributes();

        try {
            $validated = Validator::make(request()->all(), [
                'business' => ['required', 'array'],
                'business.name' => ['nullable'],
                'business.name.*' => ['nullable', 'string', 'max:255'],
                'business.vat_number' => ['nullable', 'string', 'max:255'],
                'business.reg_number' => ['nullable', 'string', 'max:255'],
                'business.is_seller' => ['nullable', 'boolean'],
                'business.seller_slug' => [
                    request()->boolean('business.is_seller') ? 'required' : 'nullable',
                    'string',
                    'max:255',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    \Illuminate\Validation\Rule::unique('mfw_accounts_account_business', 'seller_slug')->ignore((int) $account->id, 'user_id'),
                ],
            ], [], $attributes)->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->responseError((string) $message);
                }
            }

            return $this;
        }

        $this->persistBusinessDetails($account, $validated['business'] ?? []);
        $this->responseSuccess(__('mfw-accounts::ui.client.is_saved'));
        $this->responseElement('client_id', $account->id);

        return $this;
    }

    public function handleClientAgentAction(): self
    {
        $client = Account::query()->find((int) request('client_id'));
        if (!$client || $client->isAgent() || !$client->isCompany()) {
            $this->responseError(__('mfw-accounts::ui.client.not_found'));

            return $this;
        }

        $agentAction = request()->string('agent_action')->toString();

        if ($agentAction === 'delete') {
            $agent = AccountAgent::query()->find((int) request('agent_id'));
            if (!$agent || (int) $agent->company_id !== (int) $client->id) {
                $this->responseError(__('ui.error'));

                return $this;
            }

            $agentId = $agent->id;
            $agent->delete();

            $this->responseSuccess(__('mfw-accounts::ui.agent.deleted'));
            $this->responseElement('callback', 'handleClientAgentActionResult');
            $this->responseElement('agent_action', 'delete');
            $this->responseElement('agent_id', $agentId);
            $this->responseElement('no_agents_text', __('mfw-accounts::ui.no_agents'));

            return $this;
        }

        $rules = app(\MetaFramework\Accounts\Http\Requests\SaveAccountAgentRequest::class)->rules();
        $attributes = app(\MetaFramework\Accounts\Http\Requests\SaveAccountAgentRequest::class)->attributes();

        try {
            $validated = Validator::make(request()->all(), $rules, [], $attributes)->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->responseError((string) $message);
                }
            }

            return $this;
        }

        $agent = $agentAction === 'update'
            ? AccountAgent::query()->find((int) request('agent_id'))
            : new AccountAgent;

        if ($agentAction === 'update' && (!$agent || (int) $agent->company_id !== (int) $client->id)) {
            $this->responseError(__('ui.error'));

            return $this;
        }

        if (!in_array($agentAction, ['create', 'update'], true)) {
            $this->responseError(__('ui.error'));

            return $this;
        }

        $agent = app(AccountAgentActions::class)->persist($client, $agent, $validated);

        $this->responseSuccess(__('mfw-accounts::ui.agent.saved'));
        $this->responseElement('callback', 'handleClientAgentActionResult');
        $this->responseElement('agent_action', $agentAction);
        $this->responseElement('agent_id', $agent->id);
        $this->responseElement('no_agents_text', __('mfw-accounts::ui.no_agents'));
        $this->responseElement('agent_html', view('mfw-accounts::clients.partials.agent_card', [
            'agent' => $agent,
            'data' => $client,
        ])->render());

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
        $accountClass = AccountModel::className();
        $account = $accountClass::query()->find($accountId);

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

    public function sendWelcomeByMail(): array
    {
        $this->enableAjaxMode();

        return (new MailController)
            ->ajaxMode()
            ->setValue('token', (string) request('token'))
            ->distribute(AccountWelcome::class, (string) request('client_id'))
            ->fetchResponse();
    }

    public function validateWelcomeEmail(Request $request): array
    {
        $this->enableAjaxMode();

        $clientId = (int) request('client_id');
        $accountClass = AccountModel::className();
        $account = $accountClass::query()->find($clientId);

        if (!$account) {
            $this->responseError(__('mfw-accounts::ui.client.not_found'));

            return $this->fetchResponse();
        }

        $validator = new AccountMailValidator;
        $result = $validator->validate($account);
        $issuedPassword = (new AccountWelcomePasswordStore)->issue($account);

        $this->responseElement('data', [
            'email' => $result['email'],
            'is_valid' => $result['is_valid'],
            'is_fake' => $result['is_fake'],
            'edit_url' => route('mfw-accounts.clients.edit', $account->id),
            'preview_url' => route('mfw-accounts.clients.welcome_mail_preview', ['client' => $account, 'token' => $issuedPassword['token']]),
            'token' => $issuedPassword['token'],
            'labels' => [
                'email' => __('mfw-auth.email'),
                'valid_email' => __('mfw-accounts::ui.ClientValidEmail'),
                'invalid_email' => __('mfw-accounts::ui.ClientInvalidEmail'),
                'invalid_email_short' => __('mfw-accounts::ui.ClientInvalidEmailShort'),
                'password' => __('mfw-auth.password.label'),
                'generated_password' => __('mfw-accounts::mailer/account_welcome.generated_password'),
                'can_change_password' => __('mfw-accounts::mailer/account_welcome.can_change_password_notice'),
                'login_url' => __('mfw-accounts::mailer/account_welcome.login_url'),
            ],
        ]);

        return $this->fetchResponse();
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
            $this->persistCompanyState($account, false);

            return;
        }

        $this->persistCompanyState($account, true);
        $this->persistBusinessDetails($account, $data['business'] ?? [], $isNew);
    }

    private function persistCompanyState(Account $account, bool $isCompany): void
    {
        if (!$isCompany) {
            if ($account->business) {
                $account->business()->delete();
            }

            $account->assignUserType(UserType::ACCOUNT->value)->save();

            return;
        }

        $account->assignUserType(UserType::COMPANY->value)->save();

        if (!$account->business) {
            $account->business()->create([
                'name' => null,
                'vat_number' => null,
                'reg_number' => null,
                'is_seller' => false,
                'seller_slug' => null,
            ]);
        }
    }

    private function persistBusinessDetails(Account $account, array $business, bool $isNew = false): void
    {
        $this->persistCompanyState($account, true);
        $previousSlug = trim((string) ($account->business->seller_slug ?? ''));

        $nameValue = $business['name'] ?? null;
        $isSeller = (bool) ($business['is_seller'] ?? false);
        $sellerSlug = $isSeller
            ? Str::slug((string) ($business['seller_slug'] ?? $this->singleLocaleValue($nameValue) ?? ''))
            : null;
        $payload = [
            'name' => $isNew
                ? $this->translateNameService($nameValue, 'name')
                : $this->normalizeNameTranslations($nameValue),
            'vat_number' => $business['vat_number'] ?? null,
            'reg_number' => $business['reg_number'] ?? null,
            'is_seller' => $isSeller,
            'seller_slug' => $sellerSlug !== '' ? $sellerSlug : null,
        ];

        if ($account->business) {
            $account->business->update($payload);
        } else {
            $account->business()->create($payload);
        }

        $account->refresh()->load('business');

        $result = app(SellerConfigSkeletonWriter::class)->ensure($account->business, $previousSlug);
        if ($result['created'] ?? false) {
            $this->responseNotice(__('mfw-accounts::ui.seller_config_notice', [
                'path' => $result['relative_path'],
            ]));
        } elseif ($result['renamed'] ?? false) {
            $this->responseNotice(__('mfw-accounts::ui.seller_config_renamed_notice', [
                'path' => $result['relative_path'],
            ]));
        }
    }

    /**
     * @return array{0: ?Account, 1: bool}
     */
    private function resolveAccountForUpdate(bool $requireExisting = false): array
    {
        $accountId = (int) request('object_id');

        if ($accountId > 0) {
            $accountClass = AccountModel::className();
            $account = $accountClass::query()->find($accountId);
            if (!$account) {
                $this->responseError(__('mfw-accounts::ui.client.not_found'));

                return [null, false];
            }

            return [$account, false];
        }

        if ($requireExisting) {
            $this->responseError(__('mfw-accounts::ui.client.not_found'));

            return [null, false];
        }

        $accountClass = AccountModel::className();

        return [new $accountClass, true];
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

    private function storeSubmittedClientAddress(Account $account): void
    {
        if (!$this->hasSubmittedAddressPayload()) {
            return;
        }

        request()->merge(['object_id' => $account->id]);

        $addressAction = (new self)
            ->ajaxMode()
            ->updateClientAddressData();

        if ($addressAction->hasErrors()) {
            $this->pushMessages($addressAction);
        }
    }

    private function hasSubmittedAddressPayload(): bool
    {
        $geoData = request('mfw_google_places', []);
        if (!is_array($geoData)) {
            return false;
        }

        foreach (self::ADDRESS_PAYLOAD_SIGNAL_FIELDS as $field) {
            if (!array_key_exists($field, $geoData)) {
                continue;
            }

            $value = trim((string) $geoData[$field]);
            if ($value !== '') {
                return true;
            }
        }

        return false;
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

    /**
     * @return array<int, string>
     */
    private function resolveAccountTypes(mixed $accountType): array
    {
        if ($accountType instanceof UserType) {
            return [$accountType->value];
        }

        $types = match (true) {
            is_array($accountType) => $accountType,
            is_string($accountType) => explode(',', $accountType),
            default => [],
        };

        $allowedTypes = collect(UserType::cases())
            ->map(fn (UserType $type): string => $type->value)
            ->values()
            ->all();

        $normalizedTypes = collect($types)
            ->map(fn (mixed $type): string => trim((string) $type))
            ->filter(fn (string $type): bool => $type !== '' && $type !== 'all')
            ->intersect($allowedTypes)
            ->unique()
            ->values()
            ->all();

        return $normalizedTypes;
    }

    /**
     * @param  array<int, string>  $accountTypes
     */
    private function isCompanyOnlySearch(array $accountTypes): bool
    {
        return $accountTypes === [UserType::COMPANY->value];
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
