define(
    [
        'require', 'jquery'
    ],
    function (
        require, $
    ) {
        console.info('partner');

        $('.js-partner').each(function(i, el) {
            var $el = $(el)
                id = $el.data('id'),
                action = $el.data('action'),
                dataValue = $el.data('value')
            ;

            console.info('partner', $el, $el.data());
            require(['module/partner/' + id], function(module) {
                module.handle(action, dataValue, $el);
            });
        });
    }
);