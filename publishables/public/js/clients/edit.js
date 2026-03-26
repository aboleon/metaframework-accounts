$(function () {
    const $clientForm = $('#account-client-form');
    const $manualFixToggle = $('[name="manual_fix_address"]').first();
    const $companyToggle = $('[name="is_company"]').first();
    const $companyFields = $('#account-company-fields');
    const $companyInfoTab = $('#account-tab-company-info');
    const $companyAgentsTab = $('#account-tab-company-agents');
    const $companyInfoPane = $('#account-pane-company-info');
    const $companyAgentsPane = $('#account-pane-company-agents');
    const $infoTab = $('#account-tab-info');
    const $localeField = $('[name="locale"]').first();
    const $sellerToggle = $('[name="business[is_seller]"]').first();
    const $sellerSlugWrap = $('#account-seller-slug-wrap');
    const $sellerSlugField = $('[name="business[seller_slug]"]').first();
    const $agentsSection = $('#account-agents-section');
    const trimValue = function (value) {
        return String(value ?? '').trim();
    };
    let sellerSlugTouched = !!($sellerSlugField.length && trimValue($sellerSlugField.val()) !== '');

    if ($manualFixToggle.length && $clientForm.length) {
        $manualFixToggle.off('change.account-client').on('change.account-client', function () {
            if (!$(this).is(':checked')) {
                return;
            }

            const $placeIdField = $clientForm.find('.gmapsbar .place_id');
            if ($placeIdField.length) {
                $placeIdField.val('');
            }

            $clientForm.find('.gmapsbar input[readonly]').removeAttr('readonly');
        });
    }

    if ($clientForm.length && $manualFixToggle.length) {
        $clientForm.find('.ajaxable').off('click.account-client').on('click.account-client', function () {
            if (!$manualFixToggle.is(':checked')) {
                return;
            }

            const $placeIdField = $clientForm.find('.gmapsbar .place_id');
            if ($placeIdField.length) {
                $placeIdField.val('');
            }
        });
    }

    const slugifySellerValue = function (value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .replace(/-{2,}/g, '-');
    };

    const companyNameValue = function () {
        const selectedLocale = trimValue($localeField.val());

        if (selectedLocale !== '') {
            const $localizedField = $clientForm.find('[name="business[name][' + selectedLocale + ']"]').first();
            if ($localizedField.length) {
                return trimValue($localizedField.val());
            }
        }

        const $singleField = $clientForm.find('[name="business[name]"]').first();
        if ($singleField.length) {
            return trimValue($singleField.val());
        }

        return '';
    };

    const syncSellerSlug = function (force) {
        if (!$sellerSlugField.length) {
            return;
        }

        if (!force && sellerSlugTouched && trimValue($sellerSlugField.val()) !== '') {
            return;
        }

        $sellerSlugField.val(slugifySellerValue(companyNameValue()));
    };

    const syncSellerState = function () {
        if (!$sellerToggle.length || !$sellerSlugWrap.length) {
            return;
        }

        const isCompany = !$companyToggle.length || $companyToggle.is(':checked');
        const isSeller = isCompany && $sellerToggle.is(':checked');

        $sellerToggle.prop('disabled', !isCompany);
        $sellerSlugWrap.toggleClass('d-none', !isSeller);
        $sellerSlugWrap.toggle(isSeller);
        $sellerSlugField.prop('disabled', !isSeller);

        if (isSeller) {
            syncSellerSlug(false);
        }
    };

    const showTab = function ($tab) {
        if (!$tab.length || $tab.hasClass('d-none')) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance($tab[0]).show();

            return;
        }

        if (typeof $tab.tab === 'function') {
            $tab.tab('show');
            return;
        }

        const targetSelector = $tab.attr('data-bs-target') || $tab.attr('href');
        if (!targetSelector) {
            return;
        }

        $('.nav-tabs .nav-link').removeClass('active').attr('aria-selected', 'false');
        $('.tab-content .tab-pane').removeClass('show active');
        $tab.addClass('active').attr('aria-selected', 'true');
        $(targetSelector).addClass('show active');
    };

    if ($sellerSlugField.length) {
        $sellerSlugField.off('input.account-seller-slug').on('input.account-seller-slug', function () {
            sellerSlugTouched = true;
        });
    }

    if ($sellerToggle.length) {
        $sellerToggle.off('change.account-seller').on('change.account-seller', function () {
            if ($(this).is(':checked') && $sellerSlugField.length && trimValue($sellerSlugField.val()) === '') {
                sellerSlugTouched = false;
                syncSellerSlug(true);
            }

            syncSellerState();
        });
    }

    if ($clientForm.length) {
        $clientForm.find('[name="business[name]"], [name^="business[name]["]')
            .off('input.account-seller-name')
            .on('input.account-seller-name', function () {
                syncSellerSlug(false);
            });
    }

    if ($localeField.length) {
        $localeField.off('change.account-seller-locale').on('change.account-seller-locale', function () {
            syncSellerSlug(false);
        });
    }

    if ($companyToggle.length && $companyFields.length) {
        const toggleCompanyState = function () {
            const checked = $companyToggle.is(':checked');
            const companyInfoVisible = checked;
            const companyAgentsVisible = checked && $companyAgentsTab.length && $companyAgentsPane.length;

            $companyInfoTab.toggleClass('d-none', !companyInfoVisible);
            $companyInfoPane.toggleClass('d-none', !companyInfoVisible);
            $companyFields.toggleClass('d-none', !checked);
            $companyFields.toggle(checked);

            $companyAgentsTab.toggleClass('d-none', !companyAgentsVisible);
            $companyAgentsPane.toggleClass('d-none', !companyAgentsVisible);
            $agentsSection.toggleClass('d-none', !checked);
            $agentsSection.toggle(checked);
            $companyFields.find('input, select, textarea').prop('disabled', !checked);

            const companyTabWasActive = ($companyInfoPane.length && $companyInfoPane.hasClass('active')) ||
                ($companyAgentsPane.length && $companyAgentsPane.hasClass('active'));

            if (!checked && companyTabWasActive) {
                showTab($infoTab);
            }

            if (checked && $companyInfoTab.length && !$companyInfoTab.hasClass('active') && !$companyAgentsTab.hasClass('active')) {
                showTab($companyInfoTab);
            }

            syncSellerState();
        };

        toggleCompanyState();
        $companyToggle.off('change.account-company').on('change.account-company', toggleCompanyState);
    }

    syncSellerState();

    if ($agentsSection.is('form')) {
        $agentsSection.off('submit.agent-section').on('submit.agent-section', function (event) {
            event.preventDefault();
        });

        bindAgentsSection($agentsSection);
    }
});

function submitAgentAction($form, payload) {
    const requestData = {};

    $form.find('input[name="_token"], input[name="action"], input[name="client_id"]').each(function () {
        const name = $(this).attr('name');
        if (!name) {
            return;
        }

        requestData[name] = $(this).val() ?? '';
    });

    $.each(payload, function (key, value) {
        requestData[key] = value;
    });

    mfwAjax($.param(requestData), $form, {
        keepMessages: false,
        printerOptions: {
            isDismissable: true,
        },
    });
}

function bindAgentCard($form, $card) {
    if (!$form.length || !$card.length) {
        return;
    }

    $card.find('[data-agent-action="update"]').off('click.agent-update').on('click.agent-update', function () {
        const payload = {
            agent_action: 'update',
            agent_id: $card.attr('data-agent-id') || '',
        };

        $card.find('[data-agent-field]').each(function () {
            payload[$(this).attr('data-agent-field') || ''] = $(this).val() ?? '';
        });

        submitAgentAction($form, payload);
    });

    $card.find('[data-agent-action="delete"]').off('click.agent-delete').on('click.agent-delete', function () {
        submitAgentAction($form, {
            agent_action: 'delete',
            agent_id: $(this).attr('data-agent-id') || '',
        });
    });
}

function bindAgentsSection($form) {
    $form.find('[data-agent-action="create"]').off('click.agent-create').on('click.agent-create', function () {
        const payload = {
            agent_action: 'create',
        };

        $form.find('[data-agent-create] [data-agent-create-field]').each(function () {
            payload[$(this).attr('name') || ''] = $(this).val() ?? '';
        });

        submitAgentAction($form, payload);
    });

    $form.find('[data-agent-card]').each(function () {
        bindAgentCard($form, $(this));
    });
}

window.handleClientAgentActionResult = function (result) {
    const $form = $('#account-agents-section');
    if (!$form.length || !result || !result.agent_action) {
        return;
    }

    const $agentList = $form.find('[data-agent-list]');
    const $createBlock = $form.find('[data-agent-create]');

    if (result.agent_action === 'create' && $agentList.length && result.agent_html) {
        $form.find('[data-no-agents]').remove();
        $agentList.append(result.agent_html);
        bindAgentCard($form, $agentList.children('[data-agent-card]').last());

        $createBlock.find('[data-agent-create-field]').each(function () {
            const $field = $(this);
            if ($field.is('select')) {
                this.selectedIndex = 0;
            } else {
                $field.val('');
            }
        });

        return;
    }

    if (result.agent_action === 'update' && result.agent_html) {
        const $currentCard = $form.find('[data-agent-card][data-agent-id="' + result.agent_id + '"]').first();
        if (!$currentCard.length) {
            return;
        }

        const $replacement = $(result.agent_html);
        $currentCard.replaceWith($replacement);
        bindAgentCard($form, $replacement);
        return;
    }

    if (result.agent_action === 'delete') {
        $form.find('[data-agent-card][data-agent-id="' + result.agent_id + '"]').remove();

        if (!$agentList.find('[data-agent-card]').length && !$form.find('[data-no-agents]').length && result.no_agents_text) {
            $('<p class="text-muted" data-no-agents></p>').text(result.no_agents_text).insertBefore($agentList);
        }
    }
};

function redirectClientEdit(result) {
    if (!result || !result.client_id) {
        return;
    }

    const $form = $('#account-client-form');
    const editUrl = $form.data('edit-url');
    if (!editUrl) {
        return;
    }

    window.location.href = editUrl.replace('CLIENT_ID', result.client_id);
}
