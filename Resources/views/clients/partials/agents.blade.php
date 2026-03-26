@php
    $agents = $data->agents ?? collect();
@endphp

@if ($data->isCompany())
    <form class="mt-4" id="account-agents-section" action="{{ route('mfw-accounts.clients.agents.handle', $data) }}"
        method="post" style="{{ $data->isCompany() ? '' : 'display:none' }}"
        data-ajax="{{ route('mfw-accounts.clients.agents.handle', $data) }}">
        @csrf

        <div class="d-flex justify-content-between align-items-center mb-3">
            <legend class="mb-0">{{ __('mfw-accounts::ui.Agents') }}</legend>
            <button class="btn" type="button" data-section-toggle="#account-agents-collapse" aria-expanded="true"
                aria-controls="account-agents-collapse">
                <i class="bi bi-chevron-down" data-collapse-icon></i>
            </button>
        </div>

        <div class="messages"></div>

        <div id="account-agents-collapse">
            @if ($agents->isEmpty())
                <p class="text-muted" data-no-agents>{{ __('mfw-accounts::ui.no_agents') }}</p>
            @endif

            <div data-agent-list>
                @foreach ($agents as $agent)
                    @include('mfw-accounts::clients.partials.agent_card', ['agent' => $agent, 'data' => $data])
                @endforeach
            </div>

            <div class="border rounded p-3" data-agent-create style="background:#f2f2f2">
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
        </div>
    </form>
@else
    <div class="mt-4" id="account-agents-section" style="display:none">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <legend class="mb-0">{{ __('mfw-accounts::ui.Agents') }}</legend>
            <button class="btn" type="button" data-section-toggle="#account-agents-collapse" aria-expanded="true"
                aria-controls="account-agents-collapse">
                <i class="bi bi-chevron-down" data-collapse-icon></i>
            </button>
        </div>

        <div id="account-agents-collapse">
            <p class="text-muted mb-0">{{ __('mfw-accounts::ui.save_company_before_agents') }}</p>
        </div>
    </div>
@endif
