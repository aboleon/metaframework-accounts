<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use MetaFramework\Accounts\Http\Requests\StoreVatRequest;
use MetaFramework\Accounts\Http\Requests\UpdateVatRequest;
use MetaFramework\Accounts\Models\Vat;
use MetaFramework\Controllers\Controller;
use MetaFramework\Services\Validation\ValidationTrait;
use MetaFramework\Support\Traits\Responses;
use Throwable;

class VatController extends Controller
{
    use Responses;
    use SoftDeletes;
    use ValidationTrait;

    public function index(): Renderable
    {
        return view('mfw-accounts::vat.index')->with('data', Vat::all());
    }

    public function create(): Renderable
    {
        $data = [
            'data' => new Vat,
            'route' => route('mfw-accounts.vat.store'),
        ];

        return view('mfw-accounts::vat.edit')->with($data);
    }

    public function store(StoreVatRequest $request): RedirectResponse
    {
        $this->validation($request);

        try {
            $vat = Vat::create(
                $this->validated_data['vat']
            );

            Cache::forget('vats');
            $vat->manageDefaultState();

            $this->responseSuccess(__('mfw.record_created'));
            $this->redirect_route = 'mfw-accounts.vat.index';

        } catch (Throwable $e) {
            $this->responseException($e);
        } finally {
            return $this->sendResponse();
        }
    }

    public function edit(Vat $vat): Renderable
    {
        $data = [
            'data' => $vat,
            'route' => route('mfw-accounts.vat.update', $vat),
        ];

        return view('mfw-accounts::vat.edit')->with($data);
    }

    public function update(UpdateVatRequest $request, Vat $vat): RedirectResponse
    {
        $this->validation($request);

        try {
            $vat->update(
                $this->validated_data['vat']
            );

            Cache::forget('vats');
            $vat->manageDefaultState();

            $this->responseSuccess(__('mfw.record_created'));
            $this->redirect_route = 'mfw-accounts.vat.index';

        } catch (Throwable $e) {
            $this->responseException($e);
        } finally {
            return $this->sendResponse();
        }
    }

    public function destroy(Vat $vat): RedirectResponse
    {
        try {
            if ($vat->default) {
                $fallbackVat = Vat::query()->where('id', '!=', $vat->id)->first();
                if ($fallbackVat) {
                    $fallbackVat->update(['default' => null]);
                }
                Cache::forget('default_vat_rate');
            }
            $vat->delete();
            Cache::forget('vats');
        } catch (Throwable $e) {
            $this->responseException($e, __('mfw-sellable.vat.is_used'));
        }

        return $this->sendResponse();

    }
}
