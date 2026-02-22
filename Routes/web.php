<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use MetaFramework\Accounts\Http\Controllers\AccountController;
use MetaFramework\Accounts\Http\Controllers\AjaxController;
use MetaFramework\Accounts\Http\Controllers\CashflowDocTypeController;
use MetaFramework\Accounts\Http\Controllers\CompanyController;
use MetaFramework\Accounts\Http\Controllers\CurrencyController;
use MetaFramework\Accounts\Http\Controllers\DashboardController;
use MetaFramework\Accounts\Http\Controllers\GeoController;
use MetaFramework\Accounts\Http\Controllers\InvoiceController;
use MetaFramework\Accounts\Http\Controllers\PayMeanController;
use MetaFramework\Accounts\Http\Controllers\PayMeansChannelController;
use MetaFramework\Accounts\Http\Controllers\VatController;
use MetaFramework\Accounts\Models\PDF;

$routePrefix = trim((string) config('mfw-accounts.route_prefix', 'mfw-accounts'), '/');
$routePrefix = $routePrefix !== '' ? $routePrefix : 'mfw-accounts';

Route::get($routePrefix . '/pdf/{hash?}', function ($hash) {
    return PDF::show($hash);
});


Route::prefix($routePrefix)->middleware(['web', 'auth'])->name('mfw-accounts.')->group(function () {
    Route::match(['post', 'put', 'patch'], 'ajax', AjaxController::class)->name('ajax');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('dashboard/index', [DashboardController::class, 'index']);
    Route::resource('cashflow-doctypes', CashflowDocTypeController::class)
        ->except(['show'])
        ->parameters(['cashflow-doctypes' => 'cashflowDocType']);
    Route::resource('pay-means', PayMeanController::class)
        ->except(['show'])
        ->parameters(['pay-means' => 'payMean']);
    Route::get('company/edit', [CompanyController::class, 'edit'])->name('company.edit');
    Route::put('company', [CompanyController::class, 'update'])->name('company.update');
    Route::get('currency/index', [CurrencyController::class, 'index'])->name('currency.index');
    Route::prefix('PayMeansChannels')->name('pay-mean-channels.')->group(function () {
        Route::get('index/{payMean?}', [PayMeansChannelController::class, 'index'])->name('index');
        Route::post('make', [PayMeansChannelController::class, 'store'])->name('store');
        Route::match(['get', 'post'], 'edit/{payMeansChannel}', [PayMeansChannelController::class, 'edit'])->name('edit');
        Route::match(['post', 'delete'], 'remove/{payMeansChannel}', [PayMeansChannelController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('search', [AccountController::class, 'search'])->name('search');
        Route::get('index', [AccountController::class, 'index']);
        Route::get('add', [AccountController::class, 'create']);
        Route::get('edit/{client}', [AccountController::class, 'edit']);
        Route::get('dashboard/{client}', [AccountController::class, 'dashboard']);
        Route::get('{client}/dashboard', [AccountController::class, 'dashboard'])->name('dashboard');
    });
    Route::resource('clients', AccountController::class)->except(['show']);
    Route::resource('vat', VatController::class);

    Route::prefix('geo')->name('geo.')->group(function () {
        Route::get('/', [GeoController::class, 'index'])->name('index');
        Route::get('index', [GeoController::class, 'index']);
        Route::any('{any}', [GeoController::class, 'index'])->where('any', '.*');
    });

    Route::resource('invoices', InvoiceController::class)->except(['show']);
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::get('invoices/mail-preview/{hash}', [InvoiceController::class, 'mailPreview'])->name('invoices.mail_preview');
});
