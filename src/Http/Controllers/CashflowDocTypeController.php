<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use MetaFramework\Accounts\Http\Requests\SaveCashflowDocTypeRequest;
use MetaFramework\Accounts\Models\CashflowDocTypes;
use MetaFramework\Accounts\Models\Invoice;
use MetaFramework\Services\Validation\ValidationInstance;
use MetaFramework\Support\Traits\Responses;
use Throwable;

class CashflowDocTypeController
{
    use Responses;

    public function index(): Renderable
    {
        $docTypes = CashflowDocTypes::withCount('invoices')
            ->orderBy('id')
            ->get();

        return view('mfw-accounts::cashflow.doctypes.index')->with('data', $docTypes);
    }

    public function create(): Renderable
    {
        return view('mfw-accounts::cashflow.doctypes.edit')->with([
            'data'  => new CashflowDocTypes,
            'route' => route('mfw-accounts.cashflow-doctypes.store'),
        ]);
    }

    public function store(SaveCashflowDocTypeRequest $request): RedirectResponse
    {
        try {
            CashflowDocTypes::create($this->validatedData($request));
            $this->responseSuccess(__('mfw::mfw.record_created'));
            $this->redirectTo(route('mfw-accounts.cashflow-doctypes.index'));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        return $this->sendResponse();
    }

    public function edit(CashflowDocTypes $cashflowDocType): Renderable
    {
        return view('mfw-accounts::cashflow.doctypes.edit')->with([
            'data'  => $cashflowDocType,
            'route' => route('mfw-accounts.cashflow-doctypes.update', $cashflowDocType),
        ]);
    }

    public function update(SaveCashflowDocTypeRequest $request, CashflowDocTypes $cashflowDocType): RedirectResponse
    {
        try {
            $cashflowDocType->update($this->validatedData($request, $cashflowDocType));
            $this->responseSuccess(__('mfw::mfw.record_updated'));
            $this->redirectTo(route('mfw-accounts.cashflow-doctypes.index'));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        return $this->sendResponse();
    }

    public function destroy(CashflowDocTypes $cashflowDocType): RedirectResponse
    {
        if (Invoice::where('doc_type', $cashflowDocType->id)->exists()) {
            $this->responseError(__('mfw-accounts::ui.hasAssociatedDocTypes'));

            return $this->sendResponse();
        }

        try {
            $cashflowDocType->delete();
            $this->responseSuccess(__('mfw::mfw.record_deleted'));
        } catch (Throwable $exception) {
            $this->responseException($exception);
        }

        $this->redirectTo(route('mfw-accounts.cashflow-doctypes.index'));

        return $this->sendResponse();
    }

    private function validatedData(SaveCashflowDocTypeRequest $request, ?CashflowDocTypes $cashflowDocType = null): array
    {
        $validation = new ValidationInstance;
        $validation->validation($request);
        $data = $validation->validatedData();
        $data = is_array($data) ? $data : [];

        $slugBase = $this->resolveSlugBase($data);
        $data['slug'] = $this->resolveUniqueSlug($slugBase, $cashflowDocType?->id);

        return $data;
    }

    private function resolveSlugBase(array $data): string
    {
        $name = $data['admin_name'] ?? $data['name'] ?? [];

        if (is_array($name)) {
            $preferredLocale = app()->getLocale();
            $fallbackLocale = config('app.fallback_locale');
            $value = $name[$preferredLocale] ?? $name[$fallbackLocale] ?? null;

            if (!$value) {
                foreach ($name as $item) {
                    if (is_string($item) && trim($item) !== '') {
                        $value = $item;
                        break;
                    }
                }
            }

            return is_string($value) ? $value : 'doc-type';
        }

        return is_string($name) && trim($name) !== '' ? $name : 'doc-type';
    }

    private function resolveUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base !== '' ? $base : 'doc-type';

        $query = CashflowDocTypes::query()->where('slug', $slug);
        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if (!$query->exists()) {
            return $slug;
        }

        $suffix = 2;
        do {
            $candidate = $slug . '-' . $suffix;
            $candidateQuery = CashflowDocTypes::query()->where('slug', $candidate);
            if ($ignoreId) {
                $candidateQuery->whereKeyNot($ignoreId);
            }
            if (!$candidateQuery->exists()) {
                return $candidate;
            }
            $suffix++;
        } while (true);
    }
}
