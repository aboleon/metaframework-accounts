<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use MetaFramework\Accounts\Http\Requests\SavePayMeanRequest;
use MetaFramework\Accounts\Models\Invoice;
use MetaFramework\Accounts\Models\PayMeans;
use MetaFramework\Services\Validation\ValidationInstance;
use MetaFramework\Support\Traits\Responses;
use Throwable;

class PayMeanController
{
    use Responses;

    public function index(): Renderable
    {
        return view('mfw-accounts::paymeans.index')->with('data', PayMeans::withCount('channels')->get());
    }

    public function create(): Renderable
    {
        return view('mfw-accounts::paymeans.edit')->with([
            'data'  => new PayMeans,
            'route' => route('mfw-accounts.pay-means.store'),
        ]);
    }

    public function store(SavePayMeanRequest $request): RedirectResponse
    {
        try {
            PayMeans::create($this->validatedData($request));
            $this->responseSuccess(__('mfw.record_created'));
            $this->redirectTo(route('mfw-accounts.pay-means.index'));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        return $this->sendResponse();
    }

    public function edit(PayMeans $payMean): Renderable
    {
        return view('mfw-accounts::paymeans.edit')->with([
            'data'  => $payMean,
            'route' => route('mfw-accounts.pay-means.update', $payMean),
        ]);
    }

    public function update(SavePayMeanRequest $request, PayMeans $payMean): RedirectResponse
    {
        try {
            $payMean->update($this->validatedData($request));
            $this->responseSuccess(__('mfw.record_updated'));
            $this->redirectTo(route('mfw-accounts.pay-means.index'));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        return $this->sendResponse();
    }

    public function destroy(PayMeans $payMean): RedirectResponse
    {
        if (Invoice::where('pay_mean', $payMean->id)->exists()) {
            $this->responseError(__('mfw-accounts::ui.hasAssociatedDocTypes'));

            return $this->sendResponse();
        }

        try {
            $payMean->delete();
            $this->responseSuccess(__('mfw.record_deleted'));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        $this->redirectTo(route('mfw-accounts.pay-means.index'));

        return $this->sendResponse();
    }

    private function validatedData(SavePayMeanRequest $request): array
    {
        $validation = new ValidationInstance;
        $validation->validation($request);
        $validated = $validation->validatedData();

        return is_array($validated) ? $validated : [];
    }
}


