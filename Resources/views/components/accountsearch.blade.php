<div class="row account-search {{ $class }}" id="{{ $id }}"
    data-multiple="{{ $multiple ? 'true' : 'false' }}">
    @if ($showToggle)
        <div class="col-12">
            <x-mfw-inputable::checkbox name="{{ $toggleName }}" :label="$label" :affected="$checked" :switch="true"
                class="account-search-toggle" />
        </div>
    @endif
    <div class="col-12 account-search-fields" style="{{ $showToggle && !$checked ? 'display: none;' : '' }}">
        <div id="{{ $containerId }}" class="position-relative account-search-container" data-ajax="{{ $ajaxUrl }}"
            data-input-name="{{ $clientInputName }}" data-create-url="{{ $createUrl }}"
            data-create-label="{{ $createLabel }}">
            <input type="text" class="form-control account-search-input account-search-input-clearable"
                @if (!$multiple) name="{{ $clientNameInputName }}" @endif
                placeholder="{{ $placeholder }}"
                @if (!$multiple) value="{{ $clientName }}" @endif />
            <button type="button" class="btn btn-sm btn-link account-search-clear" aria-label="{{ __('ui.clear') }}">
                <i class="bi bi-x-lg"></i>
            </button>
            @if (!$multiple)
                <input type="hidden" id="{{ $hiddenInputId }}" name="{{ $clientInputName }}"
                    class="account-search-id" value="{{ $clientId }}" />
            @endif
        </div>

        @if ($multiple)
            @if (!empty($headers))
                <div
                    class="account-search-headers d-flex align-items-center bg-secondary-subtle rounded-top fw-bold small mt-2 gap-2 p-2">
                    @foreach ($headers as $header)
                        <span class="{{ $header['class'] ?? 'flex-grow-1' }}"
                            @if (!empty($header['style'])) style="{{ $header['style'] }}" @endif>{{ $header['label'] }}</span>
                    @endforeach
                </div>
            @endif
            <div class="account-search-selected{{ !empty($headers) ? '' : ' mt-2' }}">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

@pushonce('css')
    <style>
        .account-search-container .suggestions {
            display: block;
            max-height: 340px;
            overflow: auto;
            overflow-x: hidden;
            z-index: 1050;
        }

        .account-search-container .account-search-clear {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            text-decoration: none;
            padding: 0 4px;
        }

        .account-search-container .account-search-clear:hover {
            color: #212529;
        }

        .account-search-input-clearable {
            padding-right: 28px;
        }
    </style>
@endpushonce

@pushonce('js')
    <script src="{!! asset('vendor/mfw-accounts/js/account-search.js') !!}"></script>
@endpushonce

@push('js')
    <script>
        $(function() {
            const $root = $('#{{ $id }}');
            new AccountSearch('#{{ $id }}'
                @if ($multiple)
                    , {
                        multiple: true
                    }
                @endif );

            $root.on('click', '.account-search-clear', function() {
                const $container = $(this).closest('.account-search-container');
                $container.find('.account-search-input').val('').trigger('change');
                $container.find('.account-search-id').val('');
                $container.find('.suggestions').hide();
            });
        });
    </script>
@endpush
