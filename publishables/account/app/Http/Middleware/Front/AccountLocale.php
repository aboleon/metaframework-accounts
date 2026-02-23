<?php

declare(strict_types=1);

namespace App\Http\Middleware\Front;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AccountLocale
{
    public function handle(Request $request, Closure $next): Response
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

        return $next($request);
    }
}
