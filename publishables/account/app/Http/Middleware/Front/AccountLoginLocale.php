<?php

declare(strict_types=1);

namespace App\Http\Middleware\Front;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountLoginLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) config('mfw.translatable.account_login', config('app.fallback_locale'));

        if ($locale !== '') {
            app()->setLocale($locale);
            request()->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
