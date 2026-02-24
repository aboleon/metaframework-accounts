function forceValidFloat(n) {

    if(n.length == 0) return '';

    var valids = '-0123456789',
    hasDot = false,
    r = '',
    c;

    for (var i = 0; i < n.length; ++i) {

        c = n.charAt(i);

        if ((c == '.' || c == ',') && !hasDot) {
            r += (r.length == 0) ? '0.' : '.';
            hasDot = true;
        }

        else if (valids.indexOf(c) != -1) {
            r += c;
        }
    }
    return r;
}

function calculations () {
    // Use event delegation on #callContainer so it works for dynamically added rows
    $('#callContainer')
        .off('change.calc keyup.calc', 'input.digit')
        .off('change.calc', '.vat_select select')
        .on('change.calc keyup.calc', 'input.digit', function () {
            reset_calculations();
        })
        .on('change.calc', '.vat_select select', function () {
            reset_calculations();
        });
}

function reset_calculations() {

    var total = 0,
        net_price = 0,
        net_vat = 0;

    $('#callContainer tbody tr').each(function() {
        var price = toNumber($('.price input', this).val());
        var quantity = toNumber($('.unit input', this).val());
        var prix_ht = price * quantity;

        // Get VAT rate from data-rate attribute (e.g., 20 for 20%)
        var $vatSelect = $('.vat_select select', this);
        var vatRate = parseFloat($vatSelect.find(':selected').data('rate')) || 0;
        var vatMultiplier = 1 + (vatRate / 100); // 1.20 for 20%

        var subtotalCents = Math.round(prix_ht * vatMultiplier * 100);
        var prixHtCents = Math.round(prix_ht * 100);
        var vatCents = subtotalCents - prixHtCents;

        var subtotal = subtotalCents / 100;
        var vat = vatCents / 100;

        $('.vat input', this).val(vat);
        $('.vat span', this).text(vat);
        $('td.subtotal span.subtotal', this).text(subtotal);

        total += subtotal;
        net_price += prix_ht;
        net_vat += vat;
    });

    $('#totalSUM').text(formatPrice(total));
    updateConvertedTotal(total);
    $('input[name=amountHT]').val(net_price.toFixed(2));
    $('input[name=amountTVA]').val(net_vat.toFixed(2));
}

function toNumber(value) {
    var normalized = forceValidFloat(String(value ?? ''));
    return parseFloat(normalized) || 0;
}

function updateConvertedTotal(total) {
    var $converted = $('#totalSUMConverted');
    if ($converted.length === 0) {
        return;
    }

    var sourceCurrencyId = parseInt($converted.data('convert-source-id'), 10);
    var rate = parseFloat($converted.data('rate')) || 0;
    var targetLabel = String($converted.data('convert-target-label') || '').trim();
    var currencyId = parseInt($('select[name="currency"]').val(), 10);

    if (!rate || !sourceCurrencyId || currencyId !== sourceCurrencyId) {
        $converted.addClass('d-none');
        return;
    }

    var totalConverted = total / rate;

    $converted
        .removeClass('d-none')
        .text(formatPrice(totalConverted) + (targetLabel ? ' ' + targetLabel : ''));
}

function deleteLine() {
    // Use event delegation so it works for dynamically added rows
    $('#callContainer')
        .off('click.delete', 'span.glyphicon-remove')
        .on('click.delete', 'span.glyphicon-remove', function() {
            $(this).parents('tr').remove();
            reset_calculations();
        });
}

$(function() {
    calculations();
    deleteLine();

    $('#callContainer span.glyphicon-remove:first').addClass('hidden');

    // Payment status radio - show/hide date inputs
    // --------------------------------------------
    function clearDateBeforeInvalid() {
        $('input[name="date_before"]').removeClass('is-invalid');
    }

    function updatePaidStatusUI(selectedValue) {
        // Hide all date inputs first
        $('.paid-date-input').hide();

        // Show the corresponding date input (if not upon_receival)
        if (selectedValue && selectedValue !== 'upon_receival') {
            $('.paid-date-input[data-for="' + selectedValue + '"]').show();
        }

        var showPaidNotice = selectedValue === 'date_paid';
        $('[data-paid-notice="date_paid"]').toggleClass('d-none', !showPaidNotice);

        if (selectedValue !== 'date_before') {
            clearDateBeforeInvalid();
        }
    }

    function handleInvoiceAjaxableClick(e) {
        var selectedValue = $('input[name=paid]:checked').val();
        var $dateBefore = $('input[name="date_before"]');
        var $datePaid = $('input[name="date_paid"]');

        if (selectedValue === 'date_before' && $.trim($dateBefore.val()) === '') {
            $dateBefore.addClass('is-invalid');
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }

        if (selectedValue === 'date_paid' && $.trim($datePaid.val()) === '') {
            var now = new Date();
            var day = String(now.getDate()).padStart(2, '0');
            var month = String(now.getMonth() + 1).padStart(2, '0');
            var year = now.getFullYear();

            $datePaid.val(day + '/' + month + '/' + year).trigger('change');
        }

        $dateBefore.removeClass('is-invalid');

        $('div.messages').remove();
        e.preventDefault();
        $('#uncachtableType').remove();

        $(this).append(spinner);
        $(this).find('i.spinner').fadeIn();

        if (typeof tinyMCE !== 'undefined' && typeof tinyMCE.triggerSave === 'function' && tinyMCE.editors && tinyMCE.editors.length > 0) {
            tinyMCE.triggerSave();
        }

        var form = $('#callForm'),
            ajaxableType = $(this).attr('type');

        if (typeof $(this).attr('data-object') !== 'undefined' && form.find('input[name="object"]').length) {
            form.find('input[name="object"]').val($(this).attr('data-object'));
        }

        if (ajaxableType === 'button' || ajaxableType === 'submit') {
            form.append('<input id="uncachtableType" type="hidden" name="' + $(this).attr('name') + '" value="' + $(this).val() + '"/>');
        }

        mfwAjax(form.serialize(), form);
    }

    $('input[name=paid]').on('change', function() {
        updatePaidStatusUI($(this).val());
    });

    $('#callForm .ajaxable')
        .off('click')
        .on('click', handleInvoiceAjaxableClick);

    $('#callForm').on('submit', function(e) {
        var selectedValue = $('input[name=paid]:checked').val();
        var $dateBefore = $('input[name="date_before"]');

        if (selectedValue === 'date_before' && $.trim($dateBefore.val()) === '') {
            $dateBefore.addClass('is-invalid');
            e.preventDefault();
            return;
        }

        $dateBefore.removeClass('is-invalid');
    });

    $('input[name="date_before"]').on('input change', function() {
        if ($.trim($(this).val()) !== '') {
            $(this).removeClass('is-invalid');
        }
    });

    updatePaidStatusUI($('input[name=paid]:checked').val());

    // Ajouter une ligne à la facture
    // --------------------------------------------
    $('#addLine').click(function(e) {
        e.preventDefault();
        $('#callContainer tbody tr:last').clone().appendTo('#callContainer tbody');
        var $newRow = $('#callContainer tbody tr:last');
        $newRow.find('input,textarea').val('');
        $newRow.find('span').text('').removeClass('hidden');
        $newRow.find('td.unit input').val(1);
        $newRow.find('td.price input').val(0);
        $newRow.find('td.vat input').val(0);

        reset_calculations();
    });

    // Rattacher une ancienne facture à un avoir
    // --------------------------------------------
    $('select[name=doc_type]').change(function() {
        var t = $('#attachedToInvoice');
        if($(this).val() !=4) {
            t.addClass('hidden');
        } else {
            t.removeClass('hidden');
        }
    });

    $('select[name="currency"]').change(function() {
        $('span.currency').text($(this).find(':selected').attr('data-sign'));
        updateConvertedTotal(parseAmount($('#totalSUM').text()));
    });

});

function parseAmount(text) {
    if (!text) {
        return 0;
    }

    return parseFloat(text.replace(/\s+/g, '').replace(',', '.')) || 0;
}

function formatPrice(value) {
    var number = parseFloat(value) || 0;
    return number
        .toFixed(2)
        .replace('.', ',')
        .replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

window.invoice = window.invoice || {};
window.invoice.update = function (result) {
    if (!result || !result.can_edit_expenses) {
        return;
    }

    var $row = $('#expenses-row');
    if ($row.length) {
        $row.removeClass('d-none');
    }

    if (window.expenses && typeof window.expenses.update === 'function') {
        window.expenses.update(result);
    }
};
window['invoice.update'] = window.invoice.update;
