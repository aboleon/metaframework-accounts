class AccountSearch {
    static instances = {};

    constructor(rootOrOptions = {}, extraOptions = {}) {
        let options = (typeof rootOrOptions === 'string' || rootOrOptions instanceof Element || rootOrOptions instanceof $)
            ? {root: rootOrOptions, ...extraOptions}
            : rootOrOptions;

        this.root = $(options.root || document);
        this.toggle = this.root.find(options.toggleSelector || '.account-search-toggle');
        this.fields = this.root.find(options.fieldsSelector || '.account-search-fields');
        this.container = this.root.find(options.containerSelector || '.account-search-container');
        this.input = this.root.find(options.inputSelector || '.account-search-input');
        this.hiddenInput = this.root.find(options.hiddenInputSelector || '.account-search-id');
        this.selectedContainer = this.root.find('.account-search-selected');
        this.action = options.action || 'findAccountByKeywords';
        this.callback = options.callback || 'dispatchAccount';
        this.minChars = options.minChars ?? 2;
        this.delay = options.delay ?? 500;
        this.multiple = options.multiple || this.root.data('multiple') === true || this.root.data('multiple') === 'true';
        this.inputName = this.container.data('input-name') || 'account_id';
        this.accountType = this.container.data('account-type') || 'all';
        this.renderItemCallback = options.renderItem || null;
        this.onSelectCallback = options.onSelect || null;
        this.currentRequestId = 0;
        this.pendingRequestId = 0;

        if (this.toggle.length && !this.toggle.is('input[type="checkbox"]')) {
            this.toggle = this.toggle.find('input[type="checkbox"]');
        }

        if (!this.toggle.length) {
            this.toggle = this.root.find('input[type="checkbox"]');
        }

        if (!this.container.length || !this.input.length) {
            return;
        }

        const containerId = this.container.attr('id');
        if (containerId) {
            AccountSearch.instances[containerId] = this;
        }

        this.bindToggle();
        this.bindInput();
        this.bindRemoveButtons();
        this.bindEscapeKey();
    }

    bindToggle() {
        if (!this.fields.length) {
            return;
        }

        if (!this.toggle.length) {
            this.fields.show();
            return;
        }

        const updateVisibility = () => {
            const isChecked = this.toggle.is(':checked');
            this.fields.toggle(isChecked);
            this.clearSuggestions();
        };

        updateVisibility();
        this.toggle.on('change', updateVisibility);
    }

    bindInput() {
        if (!this.input.length || !this.container.length) {
            return;
        }

        this.input.on('keyup', (e) => {
            if (e.key === 'Escape') {
                return;
            }
            const data = this.input.val() || '';
            this.clearSuggestions();
            this.currentRequestId++;
            if (data.length < this.minChars) {
                return;
            }

            const requestId = this.currentRequestId;
            setDelay(() => {
                if (requestId !== this.currentRequestId) {
                    return;
                }
                this.pendingRequestId = requestId;
                const containerId = this.container.attr('id') || '';
                const formData = `action=${this.action}&callback=${this.callback}` +
                    `&callback_container=${containerId}&data=${encodeURIComponent(data)}` +
                    `&account_type=${encodeURIComponent(this.accountType)}`;
                mfwAjax(formData, this.container);
            }, this.delay);
        });
    }

    bindEscapeKey() {
        if (!this.input.length) {
            return;
        }

        this.input.on('keydown', (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                this.input.val('');
                this.clearSuggestions();
                this.currentRequestId++;
            }
        });
    }

    bindRemoveButtons() {
        this.root.on('click', '.account-search-remove', (event) => {
            $(event.currentTarget).closest('.account-search-item').remove();
        });
    }

    clearSuggestions() {
        this.container.find('.suggestions').remove();
    }

    isAlreadySelected(id) {
        if (!this.multiple) {
            return false;
        }
        return this.selectedContainer.find(`input[value="${id}"]`).length > 0;
    }

    addSelectedItem(id, label, accountData = null) {
        if (this.isAlreadySelected(id)) {
            return;
        }

        let item;
        if (this.renderItemCallback) {
            item = $(this.renderItemCallback(id, label, this.inputName, accountData));
        } else {
            const safeLabel = $('<div>').text(label).html();
            item = $(`
                <div class="account-search-item d-flex align-items-center gap-2 mb-1 p-2 bg-light rounded">
                    <input type="hidden" name="${this.inputName}[]" value="${id}"/>
                    <span class="account-search-item-label flex-grow-1">${safeLabel}</span>
                    <button type="button" class="btn btn-sm btn-danger mfw-bg-red account-search-remove">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </div>
            `);
        }

        this.selectedContainer.append(item);
    }

    renderSuggestions(result, requestId = null) {
        if (requestId !== null && requestId !== this.currentRequestId) {
            return;
        }
        this.clearSuggestions();
        const accounts = result.accounts || [];
        let list = '<div class="suggestions"><ul>';
        accounts.forEach((account) => {
            const decodeHtml = (value) => $('<textarea>').html(value ?? '').text();
            const name = [account.first_name, account.last_name].filter(Boolean).join(' ');
            const nameText = decodeHtml(name).trim();
            const businessText = account.business ? decodeHtml(account.business).trim() : '';
            const safeName = $('<div>').text(nameText).html();
            const safeBusiness = $('<div>').text(businessText).html();
            const badge = businessText ? ` <span class="badge bg-success ms-2">${safeBusiness}</span>` : '';
            const label = `${safeName}${badge}`.trim();

            let inputLabel = nameText;
            if (!inputLabel && businessText) {
                inputLabel = businessText;
            } else if (businessText) {
                const nameLower = nameText.toLowerCase();
                const businessLower = businessText.toLowerCase();
                if (businessLower && !nameLower.includes(businessLower)) {
                    inputLabel = `${nameText} ${businessText}`.trim();
                }
            }

            const safeInputLabel = $('<div>').text(inputLabel).html();
            const isSelected = this.isAlreadySelected(account.id);
            const selectedClass = isSelected ? ' already-selected' : '';
            const accountJson = encodeURIComponent(JSON.stringify(account));
            list += `<li data-id="${account.id}" data-label="${safeInputLabel}" data-account="${accountJson}" class="${selectedClass}"><span class="text d-flex align-items-center">${label}</span></li>`;
        });

        const createUrl = this.container.data('create-url');
        const createLabel = this.container.data('create-label') || 'Create';
        if (createUrl) {
            list += `<li class="create-client-item"><a class="btn btn-xs btn-success" href="${createUrl}">${createLabel}</a></li>`;
        }

        list += '</ul></div>';

        this.container.append(list);
        this.container.find('.suggestions li:not(.create-client-item)').off('click').on('click', (event) => {
            const item = $(event.currentTarget);
            const id = item.data('id');
            const label = item.data('label') || item.find('span').text();
            let accountData = null;
            try {
                const accountJson = item.attr('data-account');
                if (accountJson) {
                    accountData = JSON.parse(decodeURIComponent(accountJson));
                }
            } catch (e) {
                console.warn('Failed to parse account data', e);
            }

            if (this.onSelectCallback) {
                this.onSelectCallback(id, label, this, accountData);
            }

            if (this.multiple) {
                this.addSelectedItem(id, label, accountData);
                this.input.val('');
            } else {
                this.input.val(label);
                this.hiddenInput.val(id);
            }

            item.parents('.suggestions').remove();
        });
    }

    static dispatch(result) {
        const containerId = result.input?.callback_container;
        const instance = containerId ? AccountSearch.instances[containerId] : null;
        if (instance) {
            instance.renderSuggestions(result, instance.pendingRequestId);
        }
    }
}

function dispatchAccount(result) {
    AccountSearch.dispatch(result);
}
