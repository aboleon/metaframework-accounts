function mfwAccountsMetaRoute(name) {
    var meta = document.querySelector('meta[name="' + name + '"]');

    return meta ? meta.getAttribute('content') : null;
}

function mfwAccountsRouteFromTemplate(metaName, token, value, fallback) {
    var template = mfwAccountsMetaRoute(metaName);

    if (!template) {
        return fallback;
    }

    return template.replace(token, encodeURIComponent(value));
}

var invoice = {
    update : function(data) {
        if (data.redirect === true) {
            window.location.replace(
                mfwAccountsRouteFromTemplate(
                    'mfw-accounts-invoice-edit-route-template',
                    '__MFW_INVOICE_ID__',
                    data.callback_id,
                    base_url()+'panel/mfw-accounts/invoices/edit/'+data.callback_id
                )
            );
        }
    },
}
var clients = {
    update : function(data) {
        if (data.redirect === true) {
            window.location.replace(
                mfwAccountsRouteFromTemplate(
                    'mfw-accounts-client-edit-route-template',
                    '__MFW_CLIENT_ID__',
                    data.callback_id,
                    base_url()+'panel/mfw-accounts/clients/edit/'+data.callback_id
                )
            );
        }
    },
    liste : function(clients) {
       $('#mfw_accounts_client').find('.suggestions').remove();
       var h = '<div class="suggestions"><ul>';
       var clientCreateRoute = mfwAccountsMetaRoute('mfw-accounts-client-create-route') || 'panel/mfw-accounts/clients/add';
       $(clients).each(function(index, client) {
        h = h.concat('<li><span class="id hidden">'+client.id+'</span><span class="text">'+client.prenom+' '+ client.nom + ' '+ client.societe +'</span></li>');
    });
       h = h.concat('</ul>');
       h = h.concat('<a class="btn btn-xs btn-success" href="'+ clientCreateRoute +'">'+ $('mfw_accounts_client').attr('data-add-client') +'</a></div>');
        $('#mfw_accounts_client').append(h).find('.suggestions').show();
        $('#mfw_accounts_client').find('.suggestions li').off().on('click', function() {
            var client_id = $(this).find('span.id').text(),
            client_name = $(this).find('span.text').text();
            $('#mfw_accounts_client').find("input[name=client_id]").val(client_id);
            $('#mfw_accounts_client').find("input[name=client_name]").val(client_name);
            $('#mfw_accounts_client').find('.suggestions').remove();
        });
    },
    card_payment : function(data)
    {
        this.liste(data.clients);
    }
}
