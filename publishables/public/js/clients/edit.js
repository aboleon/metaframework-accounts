$(function () {
    const $clientForm = $('#account-client-form');
    const $manualFixToggle = $('#manual-fix-address');
    const $companyToggle = $('#is-company');
    const $companyFields = $('#account-company-fields');
    const $agentsSection = $('#account-agents-section');

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

    if ($companyToggle.length && $companyFields.length) {
        const toggleCompanyState = function () {
            const checked = $companyToggle.is(':checked');
            $companyFields.toggle(checked);
            $agentsSection.toggle(checked);
            $companyFields.find('input, select, textarea').prop('disabled', !checked);
        };

        toggleCompanyState();
        $companyToggle.off('change.account-company').on('change.account-company', toggleCompanyState);
    }

    $('[data-section-toggle]').each(function () {
        const $button = $(this);
        const targetSelector = $button.attr('data-section-toggle');
        const $icon = $button.find('[data-collapse-icon]');

        if (!targetSelector || !$icon.length) {
            return;
        }

        const $targets = $(targetSelector + (targetSelector === '#account-address-collapse' ? ', #account-address-corrections-collapse' : ''));
        if (!$targets.length) {
            return;
        }

        const syncIcon = function (expanded) {
            $icon.toggleClass('bi-chevron-down', expanded);
            $icon.toggleClass('bi-chevron-up', !expanded);
            $button.attr('aria-expanded', expanded ? 'true' : 'false');
        };

        syncIcon($targets.first().is(':visible'));

        $button.off('click.section-toggle').on('click.section-toggle', function () {
            const expanded = $targets.first().is(':visible');
            syncIcon(!expanded);
            $targets.stop(true, true).slideToggle(220);
        });
    });

    if ($agentsSection.is('form')) {
        $agentsSection.off('submit.agent-section').on('submit.agent-section', function (event) {
            event.preventDefault();
        });

        bindAgentsSection($agentsSection);
    }
});

function submitAgentAction($form, payload) {
    mfwAjax($.param(payload), $form, {
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
