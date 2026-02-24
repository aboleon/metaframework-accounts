function mfwAccountsMetaRoute(name) {
    const meta = document.querySelector('meta[name="' + name + '"]');

    return meta ? meta.getAttribute('content') : null;
}

function mfwAccountsRouteFromTemplate(metaName, token, value, fallback) {
    const template = mfwAccountsMetaRoute(metaName);

    if (!template) {
        return fallback;
    }

    return template.replace(token, encodeURIComponent(value));
}

function bindSendInvoiceByMail() {
    bindInvoiceMailerPreview();
    bindInvoiceMailerFooter();

    $('.confirm-send-invoice').off('click').on('click', function (e) {
        e.preventDefault();

        const $btn = $(this);
        // IMPORTANT: Use attr() not data() - jQuery's data() caches values
        // and won't reflect updates made by the modal component
        const hash = $btn.attr('data-identifier');
        const modalBody = $('#mfw-simple-modal').find('.modal-body');
        const messageContainer = modalBody.find('.invoice-mailer-messages');
        const ajaxTarget = messageContainer.length ? messageContainer : modalBody;

        if (!hash) {
            console.error('No hash identifier found on button');
            return false;
        }

        console.log('Sending invoice with hash:', hash);
        setVeil(modalBody);
        mfwAjax(
            'action=sendInvoiceFromModal&hash=' + encodeURIComponent(hash) + '&callback=sendMailFromModalResponse&modal_id=mfw-simple-modal',
            ajaxTarget
        );
    });
}

function bindInvoiceMailerFooter() {
    const modal = $('#mfw-simple-modal');
    const footer = modal.find('.modal-footer');
    const confirmButton = modal.find('.btn-confirm');

    let actionsRight = modal.find('.modal-actions-right');

    if (!footer.length || !confirmButton.hasClass('confirm-send-invoice')) {
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

function bindInvoiceMailerPreview() {
    const modalEl = document.getElementById('mfw-simple-modal');
    if (!modalEl) {
        return;
    }

    modalEl.removeEventListener('show.bs.modal', handleInvoiceMailerPreviewShow);
    modalEl.addEventListener('show.bs.modal', handleInvoiceMailerPreviewShow);

    modalEl.removeEventListener('hidden.bs.modal', handleInvoiceMailerPreviewHidden);
    modalEl.addEventListener('hidden.bs.modal', handleInvoiceMailerPreviewHidden);
}

function handleInvoiceMailerPreviewShow(event) {
    const trigger = event.relatedTarget;
    if (!trigger) {
        return;
    }

    const confirmButton = $('#mfw-simple-modal').find('.btn-confirm');
    if (!confirmButton.hasClass('confirm-send-invoice')) {
        return;
    }

    const hash = $(trigger).attr('data-identifier');
    if (!hash) {
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

    const url = mfwAccountsRouteFromTemplate(
        'mfw-accounts-invoice-mail-preview-route-template',
        '__MFW_INVOICE_HASH__',
        hash,
        base_url('panel/mfw-accounts/invoices/mail-preview/' + encodeURIComponent(hash))
    );
    modalBody.html(
        '<div class="invoice-mailer-meta" style="padding: 12px 0 8px;">' +
            '<div class="invoice-mailer-messages" style="padding: 0 20px 12px;"></div>' +
            '<div class="invoice-mailer-meta-content" style="padding: 0 20px;"></div>' +
            '<div class="invoice-mailer-meta-alert" style="padding: 10px 20px 0;"></div>' +
        '</div>' +
        '<div class="invoice-mailer-preview" style="width: 100%; max-width: 620px; margin: 0 auto;">' +
            '<iframe title="Invoice preview" class="w-100 border-0" style="height: 220px;" src="about:blank"></iframe>' +
        '</div>'
    );

    loadInvoiceMailerMeta(hash, modalBody);

    const iframe = modalBody.find('iframe').get(0);
    if (!iframe) {
        return;
    }

    removeVeil();
    setVeil(modalBody);

    iframe.onload = function () {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            const height = doc.documentElement.scrollHeight || doc.body.scrollHeight;
            iframe.style.height = height + 'px';
        } catch (e) {
            iframe.style.height = '80vh';
        }
        removeVeil();
    };

    iframe.src = url;
}

function handleInvoiceMailerPreviewHidden() {
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

function loadInvoiceMailerMeta(hash, modalBody) {
    const metaContainer = modalBody.find('.invoice-mailer-meta-content');
    const alertContainer = modalBody.find('.invoice-mailer-meta-alert');
    if (!metaContainer.length) {
        return;
    }

    if (alertContainer.length) {
        alertContainer.empty().addClass('d-none');
    }

    const ajaxUrl = $('meta[name="ajax-route"]').attr('content');
    if (!ajaxUrl) {
        metaContainer.html('<div class="text-muted small">Email</div><div class="fw-semibold">—</div>');
        return;
    }

    metaContainer.html('<div class="text-muted small">Email</div><div class="fw-semibold">...</div>');

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        data: {
            action: 'validateAccountEmail',
            hash: hash,
        },
    }).done(function (result) {
        const data = result && result.data ? result.data : null;
        if (!data) {
            metaContainer.html('<div class="text-muted small">Email</div><div class="fw-semibold">—</div>');
            return;
        }

        const email = data.email || '—';
        const editUrl = data.edit_url || '#';
        const labels = data.labels || {};
        const emailLabel = labels.email || 'Email';
        const validLabel = labels.valid_email || 'Valid e-mail';
        const invalidLabel = labels.invalid_email || 'Invalid email';
        const invalidShortLabel = labels.invalid_email_short || 'Invalid email';
        const cyrillicWarning = labels.cyrillic_warning || 'This message includes Cyrillic content for a non-Cyrillic locale.';
        const isInvalid = !data.is_valid || data.is_fake;
        const hasCyrillicMismatch = data.has_cyrillic && !data.is_cyrillic_locale;
        const confirmButton = $('#mfw-simple-modal').find('.btn-confirm');

        if (isInvalid) {
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
            return;
        }

        if (alertContainer.length) {
            if (hasCyrillicMismatch) {
                alertContainer
                    .removeClass('d-none')
                    .html(
                        '<div class="alert alert-warning d-flex align-items-start gap-2 mb-0" role="alert">' +
                            '<i class="bi bi-exclamation-triangle-fill mt-1"></i>' +
                            '<div class="fw-semibold">' + cyrillicWarning + '</div>' +
                        '</div>'
                    );
            } else {
                alertContainer.empty().addClass('d-none');
            }
        }

        if (confirmButton.length) {
            if (hasCyrillicMismatch) {
                confirmButton.prop('disabled', true).addClass('disabled d-none');
            } else {
                confirmButton.prop('disabled', false).removeClass('disabled d-none');
            }
        }

        metaContainer.html(
            '<div class="d-flex align-items-center justify-content-between gap-3">' +
                '<div>' +
                    '<div class="text-muted small">' + emailLabel + '</div>' +
                    '<div class="fw-semibold">' + email + '</div>' +
                '</div>' +
                '<div class="px-3 py-2 rounded d-flex align-items-center gap-2 bg-success-light text-success">' +
                    '<i class="bi bi-check-circle-fill"></i>' +
                    '<span class="fw-semibold">' + validLabel + '</span>' +
                '</div>' +
            '</div>'
        );
    }).fail(function () {
        metaContainer.html('<div class="text-muted small">Email</div><div class="fw-semibold">—</div>');
    });
}
