<?php

declare(strict_types=1);

use App\Http\Controllers\Front\Account\AccountAuthController;
use App\Http\Controllers\Front\Account\AccountPortalController;
use App\Http\Middleware\Front\AccountLocale;
use App\Http\Middleware\Front\AccountLoginLocale;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')->group(function () {
    Route::middleware(['guest:account', AccountLoginLocale::class])->group(function () {
        Route::get('login', [AccountAuthController::class, 'create'])->name('login');
        Route::post('login', [AccountAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth:account', AccountLocale::class])->group(function () {
        Route::get('/', [AccountPortalController::class, 'dashboard'])->name('dashboard');
        Route::post('logout', [AccountAuthController::class, 'destroy'])->name('logout');
    });
});
