@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ trans_choice('mfw-accounts::ui.Location', $locations->count()) }}
    </h4>
@endsection

@section('content')

    <table id="datatable-responsive" class="form table table-striped table-bordered dt-responsive nowrap table-hover"
           cellspacing="0" width="100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{ trans_choice('mfw-accounts::ui.Location', 1) }}</th>
            <th>{!! trans_choice('ui.geo.country',1) !!}</th>
            <th>{!! trans_choice('mfw-accounts::ui.Client',2) !!}</th>
        </tr>
        </thead>
        <tbody>

        @forelse($locations as $index => $location)
            <tr class="hover">
                <td>{{ $index + 1 }}</td>
                <td>{{ $location->locality ?? '-' }}</td>
                <td>{{ \MetaFramework\GooglePlaces\Accessors\Country::getCountryNameByCode($location->country_code) }}</td>
                <td>{{ $location->clients_count }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">{{ __('ui.NoSearchResult') }}</td>
            </tr>
        @endforelse

        </tbody>
    </table>
@endsection

@push('js')

@endpush
