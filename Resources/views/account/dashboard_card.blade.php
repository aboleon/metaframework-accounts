<div class="card">
    <div class="card-body hover" style="padding-bottom: 10px;">
        @php
            use MetaFramework\Accounts\Accessors\AddressAccessor;$address = $client->address->first();
        @endphp
        <div>
            {{ $client->first_name . ' ' . $client->last_name }}
            @if($client->business)
                <strong>{{ $client->business->name }}</strong>
            @endif
        </div>
        @if($address)
            <address>
                {!! AddressAccessor::printLocaleAddress($address) !!}
            </address>
        @endif

        @if($client->business?->vat_number)
            <div>{!! __('mfw-accounts::ui.VAT') . ' : ' . $client->business->vat_number !!}</div>
        @endif

        @if(!empty($client->phone))
            <div>{!! __('mfw-accounts::ui.phone') . ' : ' . $client->phone !!}</div>
        @endif

        @if(!empty($client->email))
            <div>{!! 'E-mail : ' . $client->email !!}</div>
        @endif

        <div class="clearfix" style="padding-top: 10px;">
            <form class="form" method="post" action="{{ route('mfw-accounts.invoices.store') }}">
                @csrf
                <input type="hidden" name="account_id" value="{{ $client->id }}">
                <div class="mfw-accounts inline-form">
                    {!! \MetaFramework\Accounts\Helpers\AccountsHelper::selectDocTypes(type: 'invoice', exclude: ['duplicata']) !!}
                </div>
                <button type="submit" class='mfw-accounts inline-form btn btn-success'>
                    {{ __('mfw-accounts::ui.AddPayDocTypes') }}
                </button>
            </form>
            <ul class="mfw-actions pull-right">
                <x-mfw::edit-link :route="route('mfw-accounts.clients.edit', $client->id)"/>
                <x-mfw::delete-modal-link reference="{{ $client->id }}"/>
            </ul>
            <x-mfw::modal :route="route('mfw-accounts.clients.destroy', $client->id)"
                          title="{{ __('ui.delete') }}"
                          question="{!! __('ui.confirm') !!}"
                          reference="destroy_{{ $client->id }}"/>
        </div>
    </div>
</div>
