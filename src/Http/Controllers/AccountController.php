<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use MetaFramework\Accessors\Locale;
use MetaFramework\Accounts\Http\Requests\SaveAccountClientRequest;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Models\AccountAddress;
use MetaFramework\Accounts\Models\Invoice;
use MetaFramework\Accounts\Support\AccountModel;
use MetaFramework\Services\GooglePlacesTranslator;
use MetaFramework\Services\Validation\ValidationInstance;

class AccountController
{
    public function index(Request $request): View
    {
        $filters = $request->only([
            'client_type',
            'date_operator',
            'date',
            'date2',
            'account_id',
            'client_name',
            'sort_by',
            'sort_order',
        ]);

        $accountClass = AccountModel::className();
        $clientsQuery = $accountClass::query()
            ->select(
                'users.id',
                'users.account_id',
                'users.first_name',
                'users.last_name',
                'users.locale',
                'users.email',
                'users.phone',
                'users.created_at',
            )
            ->with(['address' => fn ($q) => $q->where('billing', 1), 'business'])
            ->withCount([
                'invoices' => fn ($query) => $query->whereIn('doc_type', [1, 5]),
            ])
            ->filters($filters);
        $clientsQuery = $accountClass::applyClientIndexQuery($clientsQuery, $filters);

        $sortBy = $filters['sort_by'] ?? 'date_created';
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'desc'));
        $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'desc';

        if ($sortBy === 'name') {
            $this->applyNameSort($clientsQuery, $sortOrder);
        } elseif ($sortBy === 'invoice_count') {
            $clientsQuery->orderBy('invoices_count', $sortOrder);
        } else {
            $clientsQuery->orderBy('created_at', $sortOrder);
        }

        $clients = $clientsQuery->paginate(15)->appends($filters);
        $customViewData = $accountClass::clientIndexViewData($clients, $filters, $request);

        return view('mfw-accounts::clients.index')->with([
            'clients' => $clients,
            'total' => $clientsQuery->count(),
            'u' => $request->url(),
            ...$customViewData,
        ]);
    }

    public function create(): View
    {
        return view('mfw-accounts::clients.edit')->with([
            'data' => new (AccountModel::className()),
            'address' => new AccountAddress,
        ]);
    }

    public function store(SaveAccountClientRequest $request): RedirectResponse
    {
        $accountClass = AccountModel::className();
        $client = new $accountClass;
        $this->persistClient($client, $this->validatedClientData($request));
        $this->persistAddress($client, $request);

        return redirect()
            ->route('mfw-accounts.clients.edit', $client)
            ->with('session_message', __('mfw-accounts::ui.client.is_saved'));
    }

    public function edit(Account $client): View
    {
        $client->load(['address' => fn ($q) => $q->where('billing', 1)]);

        return view('mfw-accounts::clients.edit')->with([
            'data' => $client,
            'address' => $client->address->first() ?? new AccountAddress,
        ]);
    }

    public function update(SaveAccountClientRequest $request, Account $client): RedirectResponse
    {
        $this->persistClient($client, $this->validatedClientData($request));
        $this->persistAddress($client, $request);

        return redirect()
            ->route('mfw-accounts.clients.edit', $client)
            ->with('session_message', __('mfw-accounts::ui.client.is_saved'));
    }

    public function dashboard(Account $client): View
    {
        $query = Invoice::where('account_id', $client->id)
            ->sale()
            ->whereNull('duplicata')
            ->with(['currencyType', 'docType', 'createdBy'])
            ->orderByDesc('id');

        $total = $query->count();
        $data = $query->paginate(15);

        $duplicatas = Invoice::query()
            ->whereIn('document_id', $data->pluck('document_id'))
            ->where('account_id', $client->id)
            ->where('duplicata', 1)
            ->get()
            ->groupBy('document_id');

        $data->each(function ($invoice) use ($duplicatas) {
            $invoice->setRelation('duplicatas', $duplicatas->get($invoice->document_id, collect()));
        });

        return view('mfw-accounts::clients.dashboard')->with([
            'data' => $data,
            'client' => $client->load(['address' => fn ($q) => $q->where('billing', 1)]),
            'total' => $total,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $name = (string) $request->get('client_name', '');
        $accountClass = AccountModel::className();

        $clients = $accountClass::query()
            ->when($name !== '', function ($query) use ($name) {
                $firstNameExpr = AccountModel::localizedColumn('first_name', app()->getLocale());
                $lastNameExpr = AccountModel::localizedColumn('last_name', app()->getLocale());

                $query
                    ->whereRaw($firstNameExpr . ' like ?', [$name . '%'])
                    ->orWhereRaw($lastNameExpr . ' like ?', [$name . '%'])
                    ->orWhere('email', 'like', $name . '%');
            })
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'clients' => $clients,
            'callback' => $request->get('callback'),
        ]);
    }

    public function destroy(Request $request, Account $client): JsonResponse|RedirectResponse
    {
        $invoices = Invoice::where('account_id', $client->id)->count();
        $error = $invoices > 0;
        $message = $error
            ? __('ui.DeleteWithError', ['error' => __('mfw-accounts::ui.errorClientInvoices')])
            : __('ui.deleted');

        if (!$error) {
            $client->delete();
        }

        if ($request->expectsJson()) {
            return response()->json(['id' => $client->id, 'error' => $error, 'message' => $message], 200);
        }

        return redirect()
            ->route('mfw-accounts.clients.index')
            ->with('session_message', $message);
    }

    protected function validatedClientData(SaveAccountClientRequest $request): array
    {
        $validation = new ValidationInstance;
        $validation->validation($request);
        $validated = $validation->validatedData();

        return is_array($validated) ? $validated : [];
    }

    protected function persistClient(Account $client, array $data): void
    {
        $client->first_name = $data['first_name'] ?? $client->first_name;
        $client->last_name = $data['last_name'] ?? $client->last_name;
        $client->email = $data['email'] ?? $client->email;
        $client->phone = $data['phone'] ?? $client->phone;
        $client->civ = $data['civ'] ?? $client->civ;
        $client->locale = $data['locale'] ?? $client->locale;

        if (!$client->exists && empty($client->password)) {
            $client->password = Str::random(40);
        }

        $client->save();
    }

    protected function persistAddress(Account $client, Request $request): void
    {
        if (!$request->has('mfw_google_places')) {
            return;
        }

        $geoData = $request->input('mfw_google_places', []);
        $billingAddress = $client->address()->where('billing', 1)->first();

        $addressData = $this->addressPayload($geoData, $billingAddress);

        if ($billingAddress) {
            $billingAddress->update($addressData);
        } else {
            $client->address()->create($addressData);
        }
    }

    /**
     * @param  array<string, mixed>  $geoData
     * @return array<string, mixed>
     */
    private function addressPayload(array $geoData, ?AccountAddress $address = null): array
    {
        $translations = $this->resolveTranslations($geoData, $address);

        return [
            'street_number' => $geoData['street_number'] ?? null,
            'route' => $translations['route'] ?? null,
            'locality' => $translations['locality'] ?? null,
            'postal_code' => $geoData['postal_code'] ?? null,
            'country_code' => $geoData['country_code'] ?? null,
            'place_id' => $geoData['place_id'] ?? null,
            'text_address' => $geoData['text_address'] ?? null,
            'lat' => $geoData['lat'] ?? null,
            'lon' => $geoData['lon'] ?? null,
            'company' => $geoData['company'] ?? null,
            'complementary' => $geoData['complementary'] ?? null,
            'administrative_area_level_1' => $translations['administrative_area_level_1'] ?? null,
            'administrative_area_level_2' => $translations['administrative_area_level_2'] ?? null,
            'billing' => 1,
        ];
    }

    /**
     * @param  array<string, mixed>  $geoData
     * @return array<string, mixed>
     */
    private function resolveTranslations(array $geoData, ?AccountAddress $address = null): array
    {
        $fields = $this->translatableFields();
        $translations = $this->existingTranslations($address, $fields);
        $newPlaceId = $geoData['place_id'] ?? null;
        $existingPlaceId = $address?->place_id;

        if ($newPlaceId && $newPlaceId === $existingPlaceId) {
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

    private function applyNameSort(Builder $clients, string $sortOrder): void
    {
        $orderLocale = config('mfw.translatable.account_login') ?: config('app.fallback_locale');
        $businessNameExpr = AccountModel::localizedColumn('b.name', $orderLocale);
        $lastNameExpr = AccountModel::localizedColumn('users.last_name', $orderLocale);
        $firstNameExpr = AccountModel::localizedColumn('users.first_name', $orderLocale);

        $clients->leftJoin('mfw_accounts_account_business as b', 'b.user_id', '=', 'users.id');
        $clients
            ->addSelect(DB::raw(
                'CASE WHEN b.id IS NOT NULL THEN COALESCE(' . $businessNameExpr . ", '')" .
                ' ELSE COALESCE(CONCAT_WS(" ", ' . $lastNameExpr . ', ' . $firstNameExpr . "), '') END as name_sort"
            ))
            ->orderBy('name_sort', $sortOrder)
            ->orderBy('users.id', $sortOrder);
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
}


