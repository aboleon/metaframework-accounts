<div class="border rounded p-3 mb-3 bg-white" data-agent-card data-agent-id="{{ $agent->id }}">
    <div class="row">
        <div class="col-md-2">
            <x-mfw-inputable::select :label="__('mfw-accounts::ui.CIV')" name="agent[{{ $agent->id }}][civ]"
                :params="['data-agent-field' => 'civ']" :affected="$agent->civ ?: 'A'" :values="[
                    'A' => __('mfw-accounts::ui.civ.M'),
                    'B' => __('mfw-accounts::ui.civ.Mme'),
                    'C' => __('mfw-accounts::ui.civ.Mlle'),
                ]" />
        </div>
        <div class="col-md-2">
            <x-mfw-inputable::select :label="__('ui.lg')" name="agent[{{ $agent->id }}][locale]"
                :params="['data-agent-field' => 'locale']" :affected="$agent->locale ?: ($data->locale ?: app()->getLocale())" :values="MetaFramework\Accessors\Locale::localesAsSelectable()" />
        </div>
        <div class="col-md-4">
            <x-mfw-inputable::input name="agent[{{ $agent->id }}][first_name]" :params="['data-agent-field' => 'first_name']" :label="__('mfw-accounts::ui.FirstName')"
                :value="$agent->first_name" />
        </div>
        <div class="col-md-4">
            <x-mfw-inputable::input name="agent[{{ $agent->id }}][last_name]" :params="['data-agent-field' => 'last_name']" :label="__('mfw-accounts::ui.LastName')"
                :value="$agent->last_name" />
        </div>
        <div class="col-md-6 mt-3">
            <x-mfw-inputable::input name="agent[{{ $agent->id }}][email]" :params="['data-agent-field' => 'email']" type="email"
                label="e-mail" :value="$agent->email" />
        </div>
        <div class="col-md-6 mt-3">
            <x-mfw-inputable::input name="agent[{{ $agent->id }}][phone]" :params="['data-agent-field' => 'phone']" :label="__('mfw-accounts::ui.phone')"
                :value="$agent->phone" />
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <button type="button" class="btn btn-info btn-sm" data-agent-action="update">{{ __('ui.save') }}</button>
        <button type="button" class="btn btn-danger btn-sm" data-agent-action="delete"
            data-agent-id="{{ $agent->id }}">
            {{ __('mfw-accounts::ui.delete') }}
        </button>
    </div>
</div>
