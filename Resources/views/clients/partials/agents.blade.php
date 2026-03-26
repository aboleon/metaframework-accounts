@php
    $agents = $data->agents ?? collect();
@endphp

@if ($data->isCompany())
    <form class="form mt-1" id="account-agents-section" action="{{ route('mfw-accounts.ajax') }}"
        data-ajax="{{ route('mfw-accounts.ajax') }}" method="post">
        @csrf
        <input type="hidden" name="action" value="handle_client_agent">
        <input type="hidden" name="client_id" value="{{ $data->id }}">

        <div class="messages"></div>

        @if ($agents->isEmpty())
            <p class="text-muted" data-no-agents>{{ __('mfw-accounts::ui.no_agents') }}</p>
        @endif

        <div data-agent-list>
            @foreach ($agents as $agent)
                @include('mfw-accounts::clients.partials.agent_card', ['agent' => $agent, 'data' => $data])
            @endforeach
        </div>

        <div class="border rounded p-3" data-agent-create>
            <h6 class="mb-3">{{ __('mfw-accounts::ui.add_agent') }}</h6>

            <div class="row">
                <div class="col-md-2">
                    <x-mfw-inputable::select :label="__('mfw-accounts::ui.CIV')" name="civ" :params="['data-agent-create-field' => 'civ']" :affected="'A'"
                        :values="[
                            'A' => __('mfw-accounts::ui.civ.M'),
                            'B' => __('mfw-accounts::ui.civ.Mme'),
                            'C' => __('mfw-accounts::ui.civ.Mlle'),
                        ]" />
                </div>
                <div class="col-md-2">
                    <x-mfw-inputable::select :label="__('ui.lg')" name="locale" :params="['data-agent-create-field' => 'locale']"
                        :affected="$data->locale ?: app()->getLocale()" :values="MetaFramework\Accessors\Locale::localesAsSelectable()" />
                </div>
                <div class="col-md-4">
                    <x-mfw-inputable::input name="first_name" :params="['data-agent-create-field' => 'first_name']" :label="__('mfw-accounts::ui.FirstName')" />
                </div>
                <div class="col-md-4">
                    <x-mfw-inputable::input name="last_name" :params="['data-agent-create-field' => 'last_name']" :label="__('mfw-accounts::ui.LastName')" />
                </div>
                <div class="col-md-6 mt-3">
                    <x-mfw-inputable::input name="email" :params="['data-agent-create-field' => 'email']" type="email" label="e-mail" />
                </div>
                <div class="col-md-6 mt-3">
                    <x-mfw-inputable::input name="phone" :params="['data-agent-create-field' => 'phone']" :label="__('mfw-accounts::ui.phone')" />
                </div>
            </div>

            <button type="button" class="btn btn-success mt-3" data-agent-action="create">
                <i class="bi bi-plus-lg"></i>{{ __('mfw-accounts::ui.add_agent') }}
            </button>
        </div>
    </form>
@else
    <div class="mt-1 d-none" id="account-agents-section">
        <p class="text-muted mb-0">{{ __('mfw-accounts::ui.save_company_before_agents') }}</p>
    </div>
@endif
