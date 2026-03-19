function bindSendAccountWelcomeByMail() {
    bindAccountWelcomeMailerPreview();
    bindAccountWelcomeMailerFooter();

    $('.confirm-send-account-welcome')
        .off('click')
        .on('click', function (event) {
            event.preventDefault();

            const button = $(this);
            const clientId = button.attr('data-model-id');
            const token = button.attr('data-identifier');
            const modalBody = $('#mfw-simple-modal').find('.modal-body');
            const messageContainer = modalBody.find('.account-welcome-mailer-messages');
            const ajaxTarget = messageContainer.length ? messageContainer : modalBody;

            if (!clientId || !token) {
                return false;
            }

            setVeil(modalBody);
            mfwAjax(
                'action=sendAccountWelcomeFromModal&client_id=' +
                    encodeURIComponent(clientId) +
                    '&token=' +
                    encodeURIComponent(token) +
                    '&callback=sendMailFromModalResponse&modal_id=mfw-simple-modal',
                ajaxTarget
            );
        });
}

function bindAccountWelcomeMailerFooter() {
    const modal = $('#mfw-simple-modal');
    const footer = modal.find('.modal-footer');
    const confirmButton = modal.find('.btn-confirm');

    let actionsRight = modal.find('.modal-actions-right');

    if (!footer.length || !confirmButton.hasClass('confirm-send-account-welcome')) {
        if (actionsRight.length) {
            if (confirmButton.length && actionsRight.has(confirmButton).length) {
                footer.append(confirmButton);
            }
            if (!actionsRight.children().length) {
                actionsRight.remove();
            }
        }
        return;
    }

    if (!actionsRight.length) {
        actionsRight = $('<div class="modal-actions-right d-flex align-items-center gap-2 ms-auto"></div>');
        footer.append(actionsRight);
    }

    if (confirmButton.length && !actionsRight.has(confirmButton).length) {
        actionsRight.append(confirmButton);
    }
}

function bindAccountWelcomeMailerPreview() {
    const modalEl = document.getElementById('mfw-simple-modal');
    if (!modalEl) {
        return;
    }

    modalEl.removeEventListener('show.bs.modal', handleAccountWelcomeMailerPreviewShow);
    modalEl.addEventListener('show.bs.modal', handleAccountWelcomeMailerPreviewShow);

    modalEl.removeEventListener('hidden.bs.modal', handleAccountWelcomeMailerPreviewHidden);
    modalEl.addEventListener('hidden.bs.modal', handleAccountWelcomeMailerPreviewHidden);
}

function handleAccountWelcomeMailerPreviewShow(event) {
    const trigger = event.relatedTarget;
    if (!trigger) {
        return;
    }

    const confirmButton = $('#mfw-simple-modal').find('.btn-confirm');
    if (!confirmButton.hasClass('confirm-send-account-welcome')) {
        return;
    }

    const clientId = $(trigger).attr('data-model-id');
    if (!clientId) {
        return;
    }

    const modalDialog = $('#mfw-simple-modal').find('.modal-dialog');
    const modalBody = $('#mfw-simple-modal').find('.modal-body');
    const isDesktop = window.innerWidth >= 680;

    modalDialog.css({
        width: isDesktop ? '620px' : '100%',
        maxWidth: isDesktop ? '620px' : '100%',
        margin: '0 auto',
    });
    modalBody.css('padding', '0');
    modalBody.html(
        '<div class="account-welcome-mailer-meta" style="padding: 12px 0 8px;">' +
            '<div class="account-welcome-mailer-messages" style="padding: 0 20px 12px;"></div>' +
            '<div class="account-welcome-mailer-meta-content" style="padding: 0 20px;"></div>' +
        '</div>' +
        '<div class="account-welcome-mailer-preview" style="width: 100%; max-width: 620px; margin: 0 auto;">' +
            '<iframe title="Account welcome preview" class="w-100 border-0" style="height: 220px;" src="about:blank"></iframe>' +
        '</div>'
    );

    loadAccountWelcomeMailerMeta(clientId, modalBody);
}

function handleAccountWelcomeMailerPreviewHidden() {
    const modalDialog = $('#mfw-simple-modal').find('.modal-dialog');
    modalDialog.css({
        width: '',
        maxWidth: '',
        margin: '',
    });

    const modalBody = $('#mfw-simple-modal').find('.modal-body');
    modalBody.css('padding', '');
    const iframe = modalBody.find('iframe').get(0);
    if (iframe) {
        iframe.src = 'about:blank';
    }
    removeVeil();
}

function loadAccountWelcomeMailerMeta(clientId, modalBody) {
    const metaContainer = modalBody.find('.account-welcome-mailer-meta-content');
    const iframe = modalBody.find('iframe').get(0);
    const ajaxUrl = $('meta[name="ajax-route"]').attr('content');

    if (!metaContainer.length || !iframe || !ajaxUrl) {
        return;
    }

    metaContainer.html('<div class="text-muted small">Email</div><div class="fw-semibold">...</div>');

    setVeil(modalBody);

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        data: {
            action: 'validateAccountWelcomeEmail',
            client_id: clientId,
        },
    })
        .done(function (result) {
            const data = result && result.data ? result.data : null;
            const confirmButton = $('#mfw-simple-modal').find('.btn-confirm');

            if (!data) {
                metaContainer.html('<div class="text-muted small">Email</div><div class="fw-semibold">—</div>');
                if (confirmButton.length) {
                    confirmButton.prop('disabled', true).addClass('disabled d-none');
                }
                removeVeil();
                return;
            }

            const email = data.email || '—';
            const editUrl = data.edit_url || '#';
            const previewUrl = data.preview_url || 'about:blank';
            const token = data.token || '';
            const labels = data.labels || {};
            const emailLabel = labels.email || 'Email';
            const validLabel = labels.valid_email || 'Valid email';
            const invalidShortLabel = labels.invalid_email_short || 'Invalid email';
            const generatedPasswordLabel = labels.generated_password || 'Generated password';
            const canChangePasswordLabel = labels.can_change_password || '';
            const isInvalid = !data.is_valid || data.is_fake;

            if (isInvalid || !token) {
                if (confirmButton.length) {
                    confirmButton.prop('disabled', true).addClass('disabled d-none');
                }
                metaContainer.html(
                    '<div class="d-flex align-items-center justify-content-between gap-3">' +
                        '<div>' +
                            '<div class="text-muted small">' + emailLabel + '</div>' +
                            '<div class="fw-semibold">' + email + '</div>' +
                        '</div>' +
                        '<a class="px-3 py-2 rounded d-flex align-items-center gap-2 bg-danger-subtle text-danger text-decoration-none" href="' + editUrl + '">' +
                            '<i class="bi bi-exclamation-triangle-fill"></i>' +
                            '<span class="fw-semibold">' + invalidShortLabel + '</span>' +
                        '</a>' +
                    '</div>'
                );
                removeVeil();
                return;
            }

            if (confirmButton.length) {
                confirmButton
                    .prop('disabled', false)
                    .removeClass('disabled d-none')
                    .attr('data-identifier', token)
                    .attr('data-model-id', clientId);
            }

            metaContainer.html(
                '<div class="d-flex flex-column gap-2">' +
                    '<div class="d-flex align-items-center justify-content-between gap-3">' +
                        '<div>' +
                            '<div class="text-muted small">' + emailLabel + '</div>' +
                            '<div class="fw-semibold">' + email + '</div>' +
                        '</div>' +
                        '<div class="px-3 py-2 rounded d-flex align-items-center gap-2 bg-success-light text-success">' +
                            '<i class="bi bi-check-circle-fill"></i>' +
                            '<span class="fw-semibold">' + validLabel + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="text-muted small">' + generatedPasswordLabel + '</div>' +
                    '<div class="text-muted small">' + canChangePasswordLabel + '</div>' +
                '</div>'
            );

            iframe.onload = function () {
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    const height = doc.documentElement.scrollHeight || doc.body.scrollHeight;
                    iframe.style.height = height + 'px';
                } catch (error) {
                    iframe.style.height = '80vh';
                }
                removeVeil();
            };

            iframe.src = previewUrl;
        })
        .fail(function () {
            const confirmButton = $('#mfw-simple-modal').find('.btn-confirm');
            metaContainer.html('<div class="text-muted small">Email</div><div class="fw-semibold">—</div>');
            if (confirmButton.length) {
                confirmButton.prop('disabled', true).addClass('disabled d-none');
            }
            removeVeil();
        });
}
