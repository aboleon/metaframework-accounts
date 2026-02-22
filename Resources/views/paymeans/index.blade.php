@extends('layouts.panel')

@section('header')
    <h4>
        {{ trans_choice('mfw-accounts::ui.payMeans.label', count($data) )}}
    </h4>
    <x-mfw::btn-index :route="route('mfw-accounts.pay-means.create')"/>
@endsection

@section('content')

    <x-mfw-support::response-messages/>

    <table class="table table-striped table-bordered dt-responsive nowrap table-hover" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th style="max-width: 50px">#</th>
          <th>{!! __('mfw-accounts::ui.Name') !!}</th>
          <th>{{ trans_choice('mfw-accounts::ui.channels', 2) }}</th>
          <th style="max-width: 140px"></th>
      </tr>
    </thead>
    <tbody>

        @forelse($data as $item)
        <tr class="hover unlinkable">
            <td>{{ $item->id }}</td>
            <td>{{ $item->name }}</td>
            <td>
                <a class="intbox{{ $item->channels_count < 1 ? ' inactive':null }}" href="{{ route('mfw-accounts.pay-mean-channels.index', ['payMean' => $item->id]) }}">
                    {{ $item->channels_count }}
                </a>
            </td>
            <td>
                <ul class="mfw-actions">
                    <x-mfw::edit-link :route="route('mfw-accounts.pay-means.edit', $item)"/>
                    <x-mfw::delete-modal-link reference="paymeans_{{ $item->id }}"/>
                </ul>
                <x-mfw::modal :route="route('mfw-accounts.pay-means.destroy', $item)"
                              title="{{ __('mfw.delete') }}"
                              question="{!! __('mfw.should_i_delete_record') !!} - <b>{{ $item->id . ' '. $item->name }}</b>"
                              reference="destroy_paymeans_{{ $item->id }}"/>
            </td>
        </tr>
        @empty
        <tr>
          <td colspan=3>{{ __('ui.NoSearchResult') }}</td>
      </tr>
      @endforelse

    </tbody>
    </table>
@endsection
