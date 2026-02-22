<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use MetaFramework\Support\Traits\Responses;
use MetaFramework\Accounts\Models\Invoice;
use MetaFramework\Accounts\Models\PayMeans;
use Throwable;

class PayMeanController extends Controller
{
    use Responses;

    public function index(): Renderable
    {
        return view('mfw-accounts::paymeans.index')->with('data', PayMeans::withCount('channels')->get());
    }

    public function create(): Renderable
    {
        return view('mfw-accounts::paymeans.edit')->with([
            'data'  => new PayMeans(),
            'route' => route('mfw-accounts.pay-means.store'),
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            PayMeans::create($this->validatedData());
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

    public function update(PayMeans $payMean): RedirectResponse
    {
        try {
            $payMean->update($this->validatedData());
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

    private function validatedData(): array
    {
        return request()->validate([
            'name'   => 'required|array',
            'name.*' => 'nullable|string',
            'type'   => 'nullable|string',
        ]);
    }
}
