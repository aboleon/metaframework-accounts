@extends('layouts.panel')
@push('css')
    {!! csscrush_tag(public_path('vendor/mfw-accounts/css/panel.css')) !!}
@endpush
@push('meta')
    <meta name="ajax-route" content="{{ route('mfw-accounts.ajax') }}">
@endpush
