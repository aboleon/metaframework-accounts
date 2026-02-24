var client_suggestion_list = function(result) {
    var c = $('#mfw_accounts_client'),
        list = '<div class="suggestions"><ul>',
        createUrl = c.data('create-url') || '#';

    if (result.clients && result.clients.length) {
        for (var i = 0; i < result.clients.length; ++i) {
            list = list.concat('<li data-id="' + result.clients[i]['id'] + '"><span class="text">' + result.clients[i]['prenom'] + ' ' + result.clients[i]['nom'] + ' ' + result.clients[i]['societe'] + '</span></li>');
        }
    }
    list = list.concat('</ul><a class="btn btn-xs btn-success" href="' + createUrl + '">' + c.data('add-term') + '</a>');
    list = list.concat('</div>');
    c.append(list).find('.suggestions').show();
    c.find('.suggestions li').click(function() {
        $('input[name=client_name]').val($(this).find('span').text());
        $('input[name=client_id]').val($(this).attr('data-id'));
        $(this).parents('.suggestions').remove();
    });
};

$('#mfw_accounts_client input[name=client_name]').keyup(function() {
    var el = $(this),
        container = $('#mfw_accounts_client'),
        data = el.val(),
        callback = container.data('callback'),
        endpoint = container.data('url');

    container.find('.suggestions').remove();

    setDelay(function() {
        if (data.length > 2 && endpoint) {
            $.get(endpoint, {client_name: data, callback: callback}, function(result) {
                if (callback && typeof window[callback] === 'function') {
                    window[callback](result);
                } else {
                    client_suggestion_list(result);
                }
            });
        } else {
            $('.suggestions').empty();
        }

    }, 500);

});
