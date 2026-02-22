<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

class AccountSearch extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $containerId = null,
        public ?string $hiddenInputId = null,
        public string $label = '',
        public ?string $placeholder = null,
        public string $toggleName = 'attach_to_account',
        public ?string $clientName = null,
        public ?int $clientId = null,
        public string $clientNameInputName = 'account_name',
        public string $clientInputName = 'account_id',
        public ?string $ajaxUrl = null,
        public bool $checked = false,
        public bool $showToggle = false,
        public bool $multiple = false,
        public array $headers = [],
        public string $class = '',
        public ?string $createUrl = null,
        public ?string $createLabel = null,
    ) {
        $this->id = $id ?: 'account-search-' . Str::random(8);
        $this->containerId = $containerId ?: $this->id . '-suggestions';
        $this->hiddenInputId = $hiddenInputId ?: $this->id . '-client-id';
        $this->ajaxUrl = $this->ajaxUrl ?: route('mfw-accounts.ajax');
        $this->placeholder = $this->placeholder ?: __('mfw-accounts::ui.search_client');
        $this->clientName = $this->clientName ?? '';
        $this->createUrl = $this->createUrl ?: route('mfw-accounts.clients.create');
        $this->createLabel = $this->createLabel ?: __('mfw-accounts::ui.NewCientAccountBtn');
    }

    /**
     * Get the view/contents that represent the component.
     */
    public function render(): View|string
    {
        return view('mfw-accounts::components.accountsearch');
    }
}
