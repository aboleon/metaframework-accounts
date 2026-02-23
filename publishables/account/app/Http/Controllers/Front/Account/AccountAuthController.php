<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Account\AccountLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountAuthController extends Controller
{
    public function create(): View
    {
        return view('front.account.login');
    }

    public function store(AccountLoginRequest $accountLoginRequest): RedirectResponse
    {
        $accountLoginRequest->authenticate();

        request()->session()->regenerate();
        $this->setAccountLocale();

        return redirect()->intended(route('account.dashboard', absolute: false));
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('account')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('account.login');
    }

    private function setAccountLocale(): void
    {
        $locale = strtolower(trim((string) (Auth::guard('account')->user()?->locale ?? '')));
        $fallbackLocale = (string) config('app.fallback_locale');
        $availableLocales = config('mfw.translatable.active_locales', []);

        if ($locale === '') {
            $locale = $fallbackLocale;
        }

        if (! empty($availableLocales) && ! in_array($locale, $availableLocales, true)) {
            $locale = $fallbackLocale;
        }

        if ($locale !== '') {
            app()->setLocale($locale);
            request()->session()->put('locale', $locale);
        }
    }
}
