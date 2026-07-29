@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ __('mfw-sellable.vat.label') }}
    </h4>
    <div class="d-flex align-items-center" id="topbar-actions">

        <x-mfw::btn-add :route="route('mfw-accounts.vat.create')"/>

        <div class="separator"></div>
    </div>
@endsection

@section('content')

    <div class="shadow p-3 mb-5 bg-body-tertiary rounded">
        <div class="row m-3">
            <div class="col">

                <x-mfw-support::response-messages/>

                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('mfw-sellable.vat.rate') }}</th>
                        <th>{{ __('mfw-sellable.vat.default') }}</th>
                        <th width="200">{{ __('mfw::mfw.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($data as $item)
                        <tr>
                            <td>{{ $item->rate }}</td>
                            <td{!! $item->default ? ' class="bg-success"':'' !!}>{{ $item->default ? __('mfw::mfw.yes') : __('mfw::mfw.no') }}</td>
                            <td>
                                <ul class="mfw-actions">
                                    <x-mfw::edit-link :route="route('mfw-accounts.vat.edit', $item->id)"/>
                                    <x-mfw::delete-modal-link reference="{{ $item->id }}"/>
                                </ul>
                                <x-mfw::modal :route="route('mfw-accounts.vat.destroy', $item->id)"
                                              title="{{__('ui.delete')}}"
                                              reference="destroy_{{ $item->id }}"/>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                {{ __('mfw::errors.no_data_in_db') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
@endsection
