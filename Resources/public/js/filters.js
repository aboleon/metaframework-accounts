var kvasir_filter = {
    container : $('#invoice-filter'),
    actionSelect : function() {
        this.container.find('.comparison_selector').on('change', function() {
            var p = $(this).parent();
            if ($(this).val() == 'between') {
                p.find('.hidden').removeClass('hidden');
                p.css('width', p.attr('data-expanded'));
            } else {
                var i = $(this).parent().find('input:last-of-type');
                if (!i.hasClass('hidden')) {
                    i.addClass('hidden');
                }
            }
        });
    },
    clear : function() {
        this.container.find('i.fa-remove').click(function() {
            $(this).parent().find('input').val('');
        });
    },
    init : function() {
        this.clear();
        this.actionSelect();
    }
}

kvasir_filter.init();
