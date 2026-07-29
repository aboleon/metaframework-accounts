<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use MetaFramework\Accessors\Locale as LocaleAccessor;
use MetaFramework\Accounts\Http\Requests\StorePayMeansChannelRequest;
use MetaFramework\Accounts\Http\Requests\UpdatePayMeansChannelRequest;
use MetaFramework\Accounts\Models\BankAccounts;
use MetaFramework\Accounts\Models\PayMeans;
use MetaFramework\Accounts\Models\PayMeansChannels;
use MetaFramework\Services\Validation\ValidationInstance;
use MetaFramework\Support\Traits\Responses;
use MetaFramework\Traits\Locale;
use Throwable;

class PayMeansChannelController
{
    use Locale;
    use Responses;

    public function index(?int $payMean = null): View
    {
        $payMeans = PayMeans::fetchPayMeansByLocale();
        $selectedPayMeanId = $payMean ?: (int) ($payMeans->keys()->first() ?? 0);
        $selectedPayMean = $payMeans->where('pay_mean_id', $selectedPayMeanId)->first() ?? $payMeans->first();
        $selectedPayMean ??= (object) ['name' => '-'];

        $channels = PayMeansChannels::query()
            ->when($selectedPayMeanId > 0, fn ($query) => $query->where('pay_mean_id', $selectedPayMeanId))
            ->get();

        return view('mfw-accounts::PayMeansChannels.index')->with([
            'data' => $channels,
            'payMeans' => $payMeans,
            'payMeanChannel' => $selectedPayMean,
        ]);
    }

    public function store(StorePayMeansChannelRequest $request): RedirectResponse
    {
        $validation = new ValidationInstance;
        $validation->validation($request);
        $validated = $validation->validatedData();
        $validated = is_array($validated) ? $validated : [];

        try {
            $channel = PayMeansChannels::query()->create([
                'pay_mean_id' => (int) ($validated['category'] ?? 0),
            ]);

            $this->responseSuccess(__('mfw::mfw.record_created'));
            $this->redirectTo(route('mfw-accounts.pay-mean-channels.edit', $channel));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        return $this->sendResponse();
    }

    public function edit(Request $request, PayMeansChannels $payMeansChannel): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            return $this->update($payMeansChannel);
        }

        return view('mfw-accounts::PayMeansChannels.edit')->with([
            'data' => $payMeansChannel->load('master'),
            'BankAccounts' => (new BankAccounts)->fetchAccounts(),
        ]);
    }

    public function update(PayMeansChannels $payMeansChannel): RedirectResponse
    {
        $validation = new ValidationInstance;
        $validation->validation(UpdatePayMeansChannelRequest::class);
        $validated = $validation->validatedData();
        $validated = is_array($validated) ? $validated : [];
        $name = $this->resolvedTranslatableName($validated);

        try {
            $payMeansChannel->update([
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'name' => $name,
            ]);

            $this->responseSuccess(__('mfw::mfw.record_updated'));
            $this->redirectTo(route('mfw-accounts.pay-mean-channels.edit', $payMeansChannel));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        return $this->sendResponse();
    }

    public function destroy(PayMeansChannels $payMeansChannel): RedirectResponse
    {
        $payMeanId = (int) $payMeansChannel->pay_mean_id;

        try {
            $payMeansChannel->delete();
            $this->responseSuccess(__('mfw::mfw.record_deleted'));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        $this->redirectTo(route('mfw-accounts.pay-mean-channels.index', ['payMean' => $payMeanId]));

        return $this->sendResponse();
    }

    private function resolvedTranslatableName(array $validated): array|string|null
    {
        $name = isset($validated['data']['name']) ? $validated['data']['name'] : null;

        if (!is_array($name)) {
            return is_string($name) ? $name : null;
        }

        if (LocaleAccessor::multilang()) {
            return $name;
        }

        $preferredLocales = array_values(array_filter([
            app()->getLocale(),
            config('app.fallback_locale'),
        ]));

        foreach ($preferredLocales as $locale) {
            $value = $name[$locale] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        foreach ($name as $value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
