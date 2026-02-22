<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use MetaFramework\Accounts\Models\BankAccounts;
use MetaFramework\Accounts\Models\PayMeans;
use MetaFramework\Accounts\Models\PayMeansChannels;
use MetaFramework\Accounts\Models\PayMeansChannelsData;
use MetaFramework\Support\Traits\Responses;
use Project;
use Throwable;

class PayMeansChannelController extends Controller
{
    use Responses;

    public function index(?int $payMean = null): View
    {
        $payMeans = PayMeans::fetchPayMeansByLocale();
        $selectedPayMeanId = $payMean ?: (int) ($payMeans->keys()->first() ?? 0);
        $selectedPayMean = $payMeans->where('pay_mean_id', $selectedPayMeanId)->first() ?? $payMeans->first();
        $selectedPayMean ??= (object) ['name' => '-'];

        $channels = PayMeansChannels::query()
            ->with('translation')
            ->when($selectedPayMeanId > 0, fn ($query) => $query->where('pay_mean_id', $selectedPayMeanId))
            ->get();

        return view('mfw-accounts::PayMeansChannels.index')->with([
            'data' => $channels,
            'payMeans' => $payMeans,
            'payMeanChannel' => $selectedPayMean,
        ]);
    }

    public function store(): RedirectResponse
    {
        request()->validate([
            'category' => 'required|integer|exists:mfw_accounts_pay_means,id',
        ]);

        try {
            $channel = PayMeansChannels::query()->create([
                'pay_mean_id' => (int) request('category'),
            ]);

            foreach (Project::locales() as $locale) {
                PayMeansChannelsData::query()->firstOrCreate([
                    'pay_channel_id' => $channel->id,
                    'lg' => $locale,
                ]);
            }

            $this->responseSuccess(__('mfw.record_created'));
            $this->redirectTo(route('mfw-accounts.pay-mean-channels.edit', $channel));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        return $this->sendResponse();
    }

    public function edit(PayMeansChannels $payMeansChannel): View|RedirectResponse
    {
        if (request()->isMethod('post')) {
            return $this->update($payMeansChannel);
        }

        return view('mfw-accounts::PayMeansChannels.edit')->with([
            'data' => $payMeansChannel->load(['master', 'translations']),
            'BankAccounts' => (new BankAccounts())->fetchAccounts(),
        ]);
    }

    public function update(PayMeansChannels $payMeansChannel): RedirectResponse
    {
        request()->validate([
            'bank_account_id' => 'nullable|integer|exists:mfw_accounts_bank_accounts,id',
            'data' => 'nullable|array',
            'data.*' => 'nullable|array',
            'data.*.name' => 'nullable|string',
            'data.*.description' => 'nullable|string',
        ]);

        try {
            $payMeansChannel->update([
                'bank_account_id' => request('bank_account_id'),
            ]);

            foreach (Project::locales() as $locale) {
                PayMeansChannelsData::query()->updateOrCreate(
                    [
                        'pay_channel_id' => $payMeansChannel->id,
                        'lg' => $locale,
                    ],
                    (array) request('data.' . $locale, []),
                );
            }

            $this->responseSuccess(__('mfw.record_updated'));
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
            PayMeansChannelsData::query()->where('pay_channel_id', $payMeansChannel->id)->delete();
            $payMeansChannel->delete();
            $this->responseSuccess(__('mfw.record_deleted'));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        $this->redirectTo(route('mfw-accounts.pay-mean-channels.index', ['payMean' => $payMeanId]));

        return $this->sendResponse();
    }
}
