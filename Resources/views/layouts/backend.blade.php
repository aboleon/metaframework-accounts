@extends('layouts.panel')
@push('css')
    {!! csscrush_tag(public_path('vendor/mfw-accounts/css/panel.css')) !!}
@endpush
@push('meta')
    @php
        $mfwAccountsRoutePrefix = trim((string) config('mfw-accounts.route_prefix', 'mfw-accounts'), '/');
        $mfwAccountsRoutePrefix = $mfwAccountsRoutePrefix !== '' ? $mfwAccountsRoutePrefix : 'mfw-accounts';
    @endphp
    <meta name="ajax-route" content="{{ route('mfw-accounts.ajax') }}">
    <meta name="mfw-accounts-route-prefix" content="{{ $mfwAccountsRoutePrefix }}">
    <meta name="mfw-accounts-invoice-edit-route-template"
        content="{{ route('mfw-accounts.invoices.edit', ['invoice' => '__MFW_INVOICE_ID__']) }}">
    <meta name="mfw-accounts-client-edit-route-template"
        content="{{ route('mfw-accounts.clients.edit', ['client' => '__MFW_CLIENT_ID__']) }}">
    <meta name="mfw-accounts-client-create-route" content="{{ route('mfw-accounts.clients.create') }}">
    <meta name="mfw-accounts-invoice-mail-preview-route-template"
        content="{{ route('mfw-accounts.invoices.mail_preview', ['hash' => '__MFW_INVOICE_HASH__']) }}">
@endpush
