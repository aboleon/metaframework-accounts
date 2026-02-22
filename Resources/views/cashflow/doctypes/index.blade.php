@php use MetaFramework\Accounts\Enum\DocTypeIncrementationEnum; @endphp
@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ trans_choice('mfw-accounts::ui.PayDocTypes', count($data)) }}
    </h4>
    <x-mfw::btn-add :route="route('mfw-accounts.cashflow-doctypes.create')"/>
@endsection

@section('content')
    <x-mfw-support::response-messages/>

    <table class="table table-striped table-bordered dt-responsive nowrap table-hover" cellspacing="0" width="100%">
        <thead>
        <tr>
            <th style="max-width: 50px">#</th>
            <th>{!! __('mfw-accounts::ui.Name') !!}</th>
            <th>{{ trans_choice('mfw-accounts::ui.invoice', 2) }}</th>
            <th>{{ __('mfw-accounts::ui.numerotation') }}</th>
            <th style="max-width: 140px"></th>
        </tr>
        </thead>
        <tbody>

        @forelse($data as $item)
            <tr class="hover unlinkable">
                <td>{{ $item->id }}</td>
                <td>{{ $item->admin_name ?: $item->name }}</td>
                <td>
                    <a class="intbox{{ $item->invoices_count < 1 ? ' inactive' : null }}"
                       href="{{ route('mfw-accounts.invoices.index', ['doc_type' => $item->id]) }}">
                        {{ $item->invoices_count }}
                    </a>
                </td>
                <td>{{ DocTypeIncrementationEnum::translated($item->numerotation) }}</td>
                <td>
                    <ul class="mfw-actions">
                        <x-mfw::edit-link :route="route('mfw-accounts.cashflow-doctypes.edit', $item)"/>
                        <x-mfw::delete-modal-link reference="doctype_{{ $item->id }}"/>
                    </ul>
                    <x-mfw::modal :route="route('mfw-accounts.cashflow-doctypes.destroy', $item->id)"
                                  title="{{ __('mfw.delete') }}"
                                  question="{!! __('mfw.should_i_delete_record') !!} - <b>{{ $item->id . ' '. ($item->admin_name ?: $item->name) }}</b>"
                                  reference="destroy_doctype_{{ $item->id }}"/>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan=4>{{ __('ui.NoSearchResult') }}</td>
            </tr>
        @endforelse

        </tbody>
    </table>
@endsection
